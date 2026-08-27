<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectResponsibleAssignment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReception;
use App\Models\ReceptionTransfer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
class ProjectController extends Controller {
 public function index(Request $request):Response {
  $projects=Project::query()->with('currentResponsibleAssignment.user')->withCount(['purchaseOrders','receptionTransfers'])
   ->when($request->filled('search'),fn($q)=>$q->where(fn($s)=>$s->where('code','like','%'.$request->string('search').'%')->orWhere('name','like','%'.$request->string('search').'%')))
   ->when($request->filled('status'),fn($q)=>$q->where('is_active',$request->string('status')->toString()==='active'))
   ->when($request->filled('responsible'),fn($q)=>$request->string('responsible')->toString()==='assigned'?$q->whereHas('currentResponsibleAssignment'):$q->whereDoesntHave('currentResponsibleAssignment'))
   ->orderBy('name')->paginate(15)->withQueryString();
  return Inertia::render('Admin/Projects/Index',['projects'=>$projects,'filters'=>['search'=>$request->string('search')->toString(),'status'=>$request->string('status')->toString(),'responsible'=>$request->string('responsible')->toString()]]);
 }
 public function show(Project $project):Response {
  $project->load(['currentResponsibleAssignment.user','responsibleAssignments.user.role','responsibleAssignments.assigner']);
  $orders=PurchaseOrder::with(['user','fournisseur'])->where('project_id',$project->id)->latest()->paginate(10)->withQueryString();
  $orderIds=PurchaseOrder::where('project_id',$project->id)->pluck('id');
  $stats=['orders'=>$orderIds->count(),'approved_orders'=>PurchaseOrder::whereIn('id',$orderIds)->where('status','approved')->count(),'order_amount'=>(float)PurchaseOrder::whereIn('id',$orderIds)->sum('amount'),'receptions'=>PurchaseOrderReception::whereIn('purchase_order_id',$orderIds)->count(),'received_quantity'=>(float)DB::table('purchase_order_reception_lines')->join('purchase_order_receptions','purchase_order_receptions.id','=','purchase_order_reception_lines.reception_id')->whereIn('purchase_order_receptions.purchase_order_id',$orderIds)->sum('quantity_received'),'transfers'=>ReceptionTransfer::where('project_id',$project->id)->where('status','confirmed')->count(),'transferred_quantity'=>(float)DB::table('reception_transfer_lines')->join('reception_transfers','reception_transfers.id','=','reception_transfer_lines.transfer_id')->where('reception_transfers.project_id',$project->id)->where('reception_transfers.status','confirmed')->sum('quantity_transferred')];
  return Inertia::render('Admin/Projects/Show',['project'=>$project,'orders'=>$orders,'stats'=>$stats,'users'=>User::with('role')->orderBy('name')->get(['id','name','email','role_id'])]);
 }
 public function store(Request $r):RedirectResponse{$d=$r->validate(['code'=>'required|string|max:100|unique:projects,code','name'=>'required|string|max:255']);Project::create([...$d,'is_active'=>true]);return back()->with('success','Chantier créé.');}
 public function update(Request $r,Project $project):RedirectResponse{$d=$r->validate(['code'=>'required|string|max:100|unique:projects,code,'.$project->id,'name'=>'required|string|max:255','is_active'=>'boolean']);$project->update($d);return back()->with('success','Chantier mis à jour.');}
 public function assign(Request $r,Project $project):RedirectResponse {
  $current=$project->currentResponsibleAssignment()->first();
  $rules=['user_id'=>'required|exists:users,id','starts_at'=>'required|date']; if($current)$rules['current_ends_at']='required|date|before:starts_at';
  $d=$r->validate($rules);
  if($current&&$current->user_id===(int)$d['user_id'])throw ValidationException::withMessages(['user_id'=>'Cet utilisateur est déjà le responsable actuel.']);
  DB::transaction(function()use($d,$project,$r,$current){if($current)$current->update(['ends_at'=>$d['current_ends_at']]);ProjectResponsibleAssignment::create(['project_id'=>$project->id,'user_id'=>$d['user_id'],'starts_at'=>$d['starts_at'],'ends_at'=>null,'assigned_by'=>$r->user()->id]);});
  return back()->with('success',$current?'Responsable remplacé et historique conservé.':'Premier responsable affecté.');
 }
}
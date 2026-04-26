import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface AppNotification {
    id: string;
    type: string;
    title: string;
    body: string;
    url: string | null;
    color: 'blue' | 'amber' | 'emerald' | 'red';
    read: boolean;
    created_at: string;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: { success?: string; error?: string };
    unread_notifications_count: number;
    show_onboarding: boolean;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface Role {
    id: number;
    name: string;
    slug: 'admin' | 'demandeur' | 'validateur' | 'caissier' | 'agent';
}

export interface ValidationLevel {
    id: number;
    name: string;
    order: number;
    description?: string;
    validators_count?: number;
}

export interface Boutique {
    id: number;
    code: string;
    name: string;
    address?: string | null;
    city?: string | null;
    is_active: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role_id?: number;
    validation_level_id?: number;
    boutique_id?: number | null;
    role?: Role;
    validation_level?: ValidationLevel;
    boutique?: Boutique | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PurchaseOrderAttachment {
    id: number;
    purchase_order_id: number;
    file_path: string;
    file_name: string;
    file_size: number;
    created_at: string;
}

export type OrderStatus = 'draft' | 'pending' | 'needs_revision' | 'approved' | 'rejected';
export type DeliveryStatus = 'ordered' | 'partially_received' | 'received';
export type PaymentStatus = 'partially_paid' | 'paid';

export interface PurchaseOrderReceptionLine {
    id: number;
    reception_id: number;
    purchase_order_line_id: number;
    quantity_received: string;
}

export interface PurchaseOrderReception {
    id: number;
    purchase_order_id: number;
    received_by: number;
    received_at: string;
    type: 'partial' | 'complete';
    notes?: string | null;
    invoice_number?: string | null;
    invoice_date?: string | null;
    invoice_amount?: string | number | null;
    receiver?: User;
    lines?: PurchaseOrderReceptionLine[];
    created_at: string;
}

export interface ModeReglement {
    id: number;
    name: string;
    slug: string;
    icon: string;
    is_active: boolean;
    order: number;
}

export interface Decaissement {
    id: number;
    purchase_order_id: number;
    recorded_by: number;
    montant: string | number;
    mode_reglement_id: number;
    mode_reglement?: ModeReglement;
    reference?: string | null;
    notes?: string | null;
    decaissement_date: string;
    recorder?: User;
    created_at: string;
    updated_at: string;
}

export interface PurchaseOrder {
    id: number;
    user_id: number;
    boutique_id?: number | null;
    fournisseur_id?: number | null;
    user?: User;
    boutique?: Boutique | null;
    fournisseur?: Fournisseur | null;
    title: string;
    description: string;
    amount: string;
    status: OrderStatus;
    order_number?: string | null;
    delivery_status?: DeliveryStatus | null;
    payment_status?: PaymentStatus | null;
    ordered_at?: string | null;
    fully_received_at?: string | null;
    current_level_order?: number;
    submitted_at?: string;
    attachments?: PurchaseOrderAttachment[];
    lines?: PurchaseOrderLine[];
    receptions?: PurchaseOrderReception[];
    decaissements?: Decaissement[];
    validation_logs?: ValidationLog[];
    comments?: OrderComment[];
    created_at: string;
    updated_at: string;
}

export interface ValidationLog {
    id: number;
    purchase_order_id: number;
    validation_level_id: number;
    user_id: number;
    action: 'approved' | 'rejected';
    comment?: string;
    delegated_by_id?: number | null;
    delegated_by?: User | null;
    validation_level?: ValidationLevel;
    user?: User;
    purchase_order?: PurchaseOrder;
    created_at: string;
}

export interface OrderComment {
    id: number;
    purchase_order_id: number;
    user_id: number;
    type: 'comment' | 'revision_request';
    content: string;
    attachment_path?: string | null;
    attachment_name?: string | null;
    attachment_size?: number | null;
    user?: User;
    created_at: string;
    updated_at: string;
}

export interface Category {
    id: number;
    name: string;
    full_name?: string;
    parent_id?: number | null;
    parent?: { id: number; name: string } | null;
    description?: string | null;
    is_active: boolean;
    articles_count?: number;
    account_code?: string | null;
    account_label?: string | null;
}

export interface AccountingEntry {
    id: number;
    purchase_order_id: number;
    entry_date: string;
    journal_code: string;
    piece_ref: string;
    account_code: string;
    account_label: string;
    aux_code?: string | null;
    aux_label?: string | null;
    entry_label: string;
    debit: string;
    credit: string;
    purchase_order?: PurchaseOrder;
    created_at: string;
}

export interface Fournisseur {
    id: number;
    name: string;
    code: string;
    email?: string | null;
    phone?: string | null;
    address?: string | null;
    city?: string | null;
    is_active: boolean;
    is_approved: boolean;
    order_lines_count?: number;
}

export interface Article {
    id: number;
    category_id?: number | null;
    category?: Category | null;
    name: string;
    reference?: string | null;
    description?: string | null;
    unit: string;
    unit_price?: string | number | null;
    is_active: boolean;
    nature: 'interne' | 'achat';
    order_lines_count?: number;
}

export interface PurchaseOrderLine {
    id?: number;
    purchase_order_id?: number;
    article_id?: number | null;
    fournisseur_id?: number | null;
    article?: Article | null;
    fournisseur?: Fournisseur | null;
    quantity: number;
    unit_price: number;
    note?: string | null;
    subtotal?: number;
    quantity_received_total?: number;
    reception_lines?: PurchaseOrderReceptionLine[];
}

export interface BudgetConsumption {
    amount: number;
    consumed: number;
    in_validation: number;
    engaged: number;
    available: number;
    percent_consumed: number;
    percent_engaged: number;
    is_exceeded: boolean;
    is_warning: boolean;
}

export interface Budget {
    id: number;
    boutique_id?: number | null;
    category_id?: number | null;
    boutique?: { id: number; name: string; code: string } | null;
    category?: { id: number; name: string } | null;
    year: number;
    month?: number | null;
    period?: string;
    amount: number;
    notes?: string | null;
    consumption?: BudgetConsumption;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface FournisseurPrix {
    fournisseur_article_id: number;
    fournisseur_id: number;
    fournisseur_name: string;
    fournisseur_is_approved: boolean;
    unit_price: number;
    reference_fournisseur: string | null;
    delai_livraison_jours: number | null;
    valide_jusqu_au: string | null;
}

export interface CatalogueTarif {
    id: number;
    article_id: number;
    article_name: string;
    article_reference: string | null;
    article_unit: string;
    category_name: string | null;
    unit_price: number;
    reference_fournisseur: string | null;
    delai_livraison_jours: number | null;
    valide_jusqu_au: string | null;
    notes: string | null;
    is_active: boolean;
}

// ── Module Caisse Épargne & Prêts ─────────────────────────────────────────────

export type PretStatut = 'draft' | 'pending' | 'approved' | 'rejected' | 'decaisse' | 'solde';
export type TransactionType = 'depot' | 'retrait';

export interface Agent {
    id: number;
    user_id?: number | null;
    boutique_id?: number | null;
    matricule: string;
    nom: string;
    prenom: string;
    telephone?: string | null;
    date_adhesion: string;
    is_active: boolean;
    photo_path?: string | null;
    boutique?: Boutique | null;
    user?: User | null;
    compte?: CompteEpargne | null;
    prets?: Pret[];
    created_at: string;
    updated_at: string;
}

export interface CompteEpargne {
    id: number;
    agent_id: number;
    solde_epargne: string | number;
    solde_bloque: string | number;
    opened_at: string;
    is_active: boolean;
    agent?: Agent;
    transactions?: TransactionEpargne[];
    created_at: string;
    updated_at: string;
}

export interface TransactionEpargne {
    id: number;
    compte_epargne_id: number;
    type: TransactionType;
    montant: string | number;
    mode_reglement_id: number;
    mode_reglement?: ModeReglement;
    reference?: string | null;
    notes?: string | null;
    transaction_date: string;
    recorded_by: number;
    recorder?: User;
    created_at: string;
    updated_at: string;
}

export interface Pret {
    id: number;
    agent_id: number;
    compte_epargne_id: number;
    montant_demande: string | number;
    montant_accorde?: string | number | null;
    motif?: string | null;
    statut: PretStatut;
    current_level_order?: number | null;
    mode_reglement_id?: number | null;
    mode_reglement?: ModeReglement | null;
    submitted_at?: string | null;
    decaisse_at?: string | null;
    solde_at?: string | null;
    recorded_by: number;
    agent?: Agent;
    compte_epargne?: CompteEpargne;
    validation_logs?: PretValidationLog[];
    remboursements?: RemboursementPret[];
    created_at: string;
    updated_at: string;
}

export interface PretValidationLog {
    id: number;
    pret_id: number;
    validation_level_id: number;
    user_id: number;
    action: 'approved' | 'rejected';
    comment?: string | null;
    delegated_by_id?: number | null;
    validation_level?: ValidationLevel;
    user?: User;
    delegated_by?: User | null;
    created_at: string;
}

export interface RemboursementPret {
    id: number;
    pret_id: number;
    montant: string | number;
    mode_reglement_id: number;
    mode_reglement?: ModeReglement;
    reference?: string | null;
    notes?: string | null;
    remboursement_date: string;
    recorded_by: number;
    recorder?: User;
    created_at: string;
}

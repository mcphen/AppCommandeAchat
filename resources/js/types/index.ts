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
    slug: 'admin' | 'employe' | 'validateur';
}

export interface Groupe {
    id: number;
    nom: string;
    code: string;
    logo_path?: string | null;
    is_active: boolean;
    entreprises_count?: number;
}

export interface Entreprise {
    id: number;
    groupe_id: number;
    groupe?: Groupe;
    nom: string;
    code: string;
    adresse?: string | null;
    ville?: string | null;
    is_active: boolean;
    users_count?: number;
}

export interface NiveauValidation {
    id: number;
    nom: string;
    slug: 'compta' | 'df' | 'dg' | 'pdg';
    ordre: number;
    description?: string | null;
    is_active: boolean;
    seuil?: SeuilValidation;
    validateurs_count?: number;
}

export interface SeuilValidation {
    id: number;
    niveau_validation_id: number;
    montant_seuil: number;
}

export interface Validateur {
    id: number;
    user_id: number;
    niveau_validation_id: number;
    is_active: boolean;
    user?: User;
    niveau_validation?: NiveauValidation;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role_id?: number;
    entreprise_id?: number | null;
    fonction?: string | null;
    role?: Role;
    entreprise?: Entreprise | null;
    niveau_validation?: NiveauValidation | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface BudgetAnnuel {
    id: number;
    entreprise_id: number;
    entreprise?: Entreprise;
    annee: number;
    libelle: string;
    montant_total: number;
    montant_consomme: number;
    is_active: boolean;
    montant_disponible?: number;
}

export type EbStatut = 'en_attente' | 'validee' | 'rejetee';
export type DapStatut = 'en_cours' | 'validee' | 'rejetee' | 'payee';
export type ValidationStatut = 'approuve' | 'rejete';

export interface ExpressionBesoinAttachment {
    id: number;
    expression_besoin_id: number;
    nom_fichier: string;
    chemin: string;
    type_mime?: string | null;
    taille?: number | null;
    created_at: string;
}

export interface ExpressionBesoin {
    id: number;
    reference: string;
    user_id: number;
    entreprise_id: number;
    user?: User;
    entreprise?: Entreprise;
    objet: string;
    description: string;
    montant: number;
    beneficiaire?: string | null;
    justification?: string | null;
    statut: EbStatut;
    motif_rejet?: string | null;
    rejected_at?: string | null;
    attachments?: ExpressionBesoinAttachment[];
    dap?: DemandeAutorisationPaiement | null;
    created_at: string;
    updated_at: string;
}

export interface ValidationDap {
    id: number;
    dap_id: number;
    niveau_validation_id: number;
    validateur_id: number;
    statut: ValidationStatut;
    commentaire?: string | null;
    validated_at: string;
    niveau_validation?: NiveauValidation;
    validateur?: User;
}

export interface Paiement {
    id: number;
    dap_id: number;
    created_by: number;
    montant: number;
    date_paiement: string;
    reference?: string | null;
    mode_paiement: 'virement' | 'cheque' | 'espece' | 'mobile_money';
    banque?: string | null;
    notes?: string | null;
    saisie_par?: User | null;
    created_at: string;
}

export interface DemandeAutorisationPaiement {
    id: number;
    reference: string;
    expression_besoin_id: number;
    budget_annuel_id?: number | null;
    created_by: number;
    statut: DapStatut;
    notes?: string | null;
    expression_besoin?: ExpressionBesoin;
    budget_annuel?: BudgetAnnuel | null;
    created_by_user?: User;
    validations?: ValidationDap[];
    paiement?: Paiement | null;
    created_at: string;
    updated_at: string;
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

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
    slug: 'admin' | 'demandeur' | 'validateur';
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

export type OrderStatus = 'draft' | 'pending' | 'approved' | 'rejected';

export interface PurchaseOrder {
    id: number;
    user_id: number;
    boutique_id?: number | null;
    user?: User;
    boutique?: Boutique | null;
    title: string;
    description: string;
    amount: string;
    status: OrderStatus;
    current_level_order?: number;
    submitted_at?: string;
    attachments?: PurchaseOrderAttachment[];
    validation_logs?: ValidationLog[];
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
    validation_level?: ValidationLevel;
    user?: User;
    purchase_order?: PurchaseOrder;
    created_at: string;
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

export type DashboardStats = {
    total_revenue: number;
    currency_en: string;
    active_merchants_count: number;
    pending_requests_count: number;
    visitors_today: number;
    visitors_total: number;
    total_products_all_sellers: number;
    total_storefront_orders: number;
    top_countries_30d: { country: string; visitors: number }[];
    top_pages_30d: { path: string; route: string; views: number; avg_seconds: number }[];
    recent_journeys: {
        session: string;
        user_id: number | null;
        total_seconds: number;
        pages: { path: string; route: string; seconds: number }[];
    }[];
};


export type AdminKpiTone = 'primary' | 'gold' | 'slate';

export type AdminKpi = {
    key: string;
    title: string;
    value: string;
    description?: string;
    tone?: AdminKpiTone;
    icon?: any;
};

export type CountriesRow = { country: string; visitors: number };
export type TopPageRow = { path: string; route: string; views: number; avg_seconds: number };

export type JourneyRow = {
    session: string;
    user_id: number | null;
    total_seconds: number;
    pages: { path: string; route: string; seconds: number }[];
};


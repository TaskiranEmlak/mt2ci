/**
 * Metin2 Web Panel - API Service
 */

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api-bridge/';

class ApiService {
    private token: string | null = null;

    constructor() {
        if (typeof window !== 'undefined') {
            this.token = localStorage.getItem('auth_token');
        }
    }

    setToken(token: string | null): void {
        this.token = token;
        if (typeof window !== 'undefined') {
            if (token) {
                localStorage.setItem('auth_token', token);
            } else {
                localStorage.removeItem('auth_token');
            }
        }
    }

    getToken(): string | null {
        return this.token;
    }

    isAuthenticated(): boolean {
        return !!this.token;
    }

    private async request<T>(
        action: string,
        method: 'GET' | 'POST' = 'GET',
        body?: object,
        params?: Record<string, string>
    ): Promise<T> {
        const url = new URL(API_URL);
        url.searchParams.set('action', action);

        if (params) {
            Object.entries(params).forEach(([key, value]) => {
                url.searchParams.set(key, value);
            });
        }

        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const response = await fetch(url.toString(), {
            method,
            headers,
            body: body ? JSON.stringify(body) : undefined,
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.error || 'API request failed');
        }

        return data;
    }

    // Auth
    async login(login: string, password: string): Promise<LoginResponse> {
        const response = await this.request<LoginResponse>('login', 'POST', { login, password });
        this.setToken(response.token);
        return response;
    }

    logout(): void {
        this.setToken(null);
    }

    // Dashboard
    async getDashboard(): Promise<DashboardData> {
        const response = await this.request<{ dashboard: DashboardData }>('dashboard');
        return response.dashboard;
    }

    // Characters
    async getCharacters(): Promise<Character[]> {
        const response = await this.request<{ characters: Character[] }>('characters');
        return response.characters;
    }

    // Shop
    async getShop(): Promise<ShopData> {
        const response = await this.request<{ shop: ShopData }>('shop');
        return response.shop;
    }

    // Events
    async getEvents(): Promise<EventsData> {
        const response = await this.request<{ events: EventsData }>('events');
        return response.events;
    }

    // Messages
    async getMessages(characterName: string): Promise<Conversation[]> {
        const response = await this.request<{ conversations: Conversation[] }>('messages', 'GET', undefined, { character: characterName });
        return response.conversations;
    }

    // Ranking
    async getRanking(type: 'level' | 'gold' | 'alignment' = 'level'): Promise<RankingData[]> {
        const response = await this.request<{ ranking: RankingData[] }>('ranking', 'GET', undefined, { type });
        return response.ranking;
    }

    // Guild
    async getGuild(characterId: number): Promise<GuildData> {
        const response = await this.request<{ guild: GuildData }>('guild', 'GET', undefined, { character_id: characterId.toString() });
        return response.guild;
    }

    async getGuildMembers(guildId: number): Promise<GuildMember[]> {
        const response = await this.request<{ members: GuildMember[] }>('guild_members', 'GET', undefined, { guild_id: guildId.toString() });
        return response.members;
    }

    // Social
    async getSocial(characterId?: number, login?: string): Promise<SocialData> {
        const params: any = {};
        if (characterId) params.character_id = characterId.toString();
        if (login) params.login = login;
        return this.request<SocialData>('social', 'GET', undefined, params);
    }

    // Statistics
    async getStatistics(characterId: number): Promise<StatisticsData> {
        const response = await this.request<{ statistics: StatisticsData }>('statistics', 'GET', undefined, { character_id: characterId.toString() });
        return response.statistics;
    }

    // Status
    async getStatus(): Promise<StatusData> {
        return this.request<StatusData>('status');
    }
}

// Types
export interface LoginResponse {
    success: boolean;
    token: string;
    account_id: number;
    login: string;
}

export interface Character {
    id: number;
    name: string;
    level: number;
    exp: number;
    exp_percent: number;
    gold: number;
    gold_formatted: string;
    won: number;
    job: number;
    job_name: string;
    alignment: number;
    alignment_rank: string;
    hp: number;
    mp: number;
    playtime: number;
    playtime_formatted: string;
}

export interface TodoItem {
    priority: string;
    icon: string;
    title: string;
    description: string;
    action: string | null;
}

export interface BiologistData {
    enabled: boolean;
    level: number;
    stage_name: string;
    can_deliver: boolean;
    remaining_formatted: string;
}

export interface DungeonData {
    key: string;
    name: string;
    available: boolean;
    status: string;
    remaining_formatted: string;
}

export interface DashboardData {
    timestamp: string;
    character_summary: {
        total_characters: number;
        main_character: {
            id: number;
            name: string;
            level: number;
            job_name: string;
            exp_percent: number;
        } | null;
        total_gold: string;
    };
    shop_summary: {
        has_shop: boolean;
        shop_name: string | null;
        total_items: number;
        total_value: string;
    };
    biologist: BiologistData;
    dungeons: DungeonData[];
    todo_list: TodoItem[];
    active_events: EventData[];
    quick_stats: {
        items_in_shop: number;
        biologist_ready: boolean;
        available_dungeons: number;
        active_events_count: number;
    };
}

export interface ShopData {
    has_shop: boolean;
    system: string;
    owner: string | null;
    name: string | null;
    items: ShopItem[];
    sold_items: ShopItem[];
    total_items: number;
    total_value_formatted: string;
}

export interface ShopItem {
    id: number;
    vnum: number;
    name: string;
    count: number;
    price_formatted: string;
    attributes: { type: number; value: number; name: string }[];
}

export interface EventData {
    id: number;
    name: string;
    description: string;
    is_active: boolean;
    remaining: string | null;
}

export interface EventsData {
    active: EventData[];
    upcoming: EventData[];
}

export interface Conversation {
    contact: string;
    last_message: string;
    last_time: string;
    messages: Message[];
}

export interface Message {
    from: string;
    to: string;
    content: string;
    time: string;
    is_mine: boolean;
}

export interface RankingData {
    rank: number;
    name: string;
    level: number;
    gold?: number;
    gold_formatted?: string;
    alignment?: number;
    alignment_rank?: string;
    job: string;
    empire: string;
}

export interface GuildData {
    has_guild: boolean;
    guild_id?: number;
    name?: string;
    level?: number;
    exp?: number;
    gold?: number;
    gold_formatted?: string;
    master_name?: string;
    member_count?: number;
    win?: number;
    draw?: number;
    loss?: number;
    ladder_point?: number;
    player_grade?: number;
    is_general?: boolean;
}

export interface GuildMember {
    name: string;
    level: number;
    job: string;
    grade: number;
    grade_name: string;
    is_general: boolean;
}

export interface SocialData {
    marriage?: {
        is_married: boolean;
        partner_name?: string;
        partner_level?: number;
        partner_job?: string;
        love_point?: number;
        married_since?: string;
        duration_days?: number;
    };
    friends?: {
        account: string;
        status: string;
    }[];
}

export interface StatisticsData {
    playtime: {
        total_seconds: number;
        total_hours: number;
        total_days: number;
        formatted: string;
    };
    level_progression: {
        level: number;
        date: string;
    }[];
    gold: {
        total_earned: number;
        total_earned_formatted: string;
        total_spent: number;
        total_spent_formatted: string;
        net: number;
        net_formatted: string;
    };
    refine: {
        total_attempts: number;
        successful: number;
        failed: number;
        success_rate: number;
    };
    fishing: {
        total_fish_caught: number;
    };
}

export interface StatusData {
    success: boolean;
    agent_version: string;
    database_connected: boolean;
    discovery_log: string[];
}

export const api = new ApiService();
export default api;

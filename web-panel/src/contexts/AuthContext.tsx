'use client';

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import api from '@/services/api';

interface AuthContextType {
    isAuthenticated: boolean;
    isLoading: boolean;
    accountId: number | null;
    login: string | null;
    signIn: (username: string, password: string) => Promise<void>;
    signOut: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [isAuthenticated, setIsAuthenticated] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [accountId, setAccountId] = useState<number | null>(null);
    const [login, setLogin] = useState<string | null>(null);

    useEffect(() => {
        // Check if user is already logged in
        const token = api.getToken();
        if (token) {
            setIsAuthenticated(true);
            // Validate token by making a request
            api.getDashboard().catch(() => {
                api.logout();
                setIsAuthenticated(false);
            });
        }
        setIsLoading(false);
    }, []);

    const signIn = async (username: string, password: string) => {
        const response = await api.login(username, password);
        setIsAuthenticated(true);
        setAccountId(response.account_id);
        setLogin(response.login);
    };

    const signOut = () => {
        api.logout();
        setIsAuthenticated(false);
        setAccountId(null);
        setLogin(null);
    };

    return (
        <AuthContext.Provider value={{ isAuthenticated, isLoading, accountId, login, signIn, signOut }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
}

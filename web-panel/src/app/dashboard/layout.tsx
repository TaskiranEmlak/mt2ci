'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useAuth } from '@/contexts/AuthContext';
import styles from './layout.module.css';

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
    const router = useRouter();
    const { isAuthenticated, isLoading, signOut, login } = useAuth();

    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            router.push('/login');
        }
    }, [isAuthenticated, isLoading, router]);

    const handleLogout = () => {
        signOut();
        router.push('/login');
    };

    if (isLoading) {
        return <div className={styles.loading}><div className="spinner"></div></div>;
    }

    return (
        <div className={styles.layout}>
            <aside className={styles.sidebar}>
                <div className={styles.logo}>⚔️ Metin2</div>

                <nav className={styles.nav}>
                    <Link href="/dashboard" className={styles.navLink}>
                        📊 Dashboard
                    </Link>
                    <Link href="/dashboard/characters" className={styles.navLink}>
                        🧙 Karakterler
                    </Link>
                    <Link href="/dashboard/shop" className={styles.navLink}>
                        🏪 Pazar
                    </Link>
                    <Link href="/dashboard/ranking" className={styles.navLink}>
                        🏆 Sıralama
                    </Link>
                    <Link href="/dashboard/events" className={styles.navLink}>
                        🔥 Etkinlikler
                    </Link>
                    <Link href="/dashboard/messages" className={styles.navLink}>
                        💬 Mesajlar
                    </Link>
                </nav>

                <div className={styles.user}>
                    <div className={styles.userInfo}>
                        <span className={styles.userName}>{login || 'Oyuncu'}</span>
                    </div>
                    <button className={styles.logoutBtn} onClick={handleLogout}>
                        Çıkış
                    </button>
                </div>
            </aside>

            <main className={styles.main}>
                {children}
            </main>
        </div>
    );
}

'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { DashboardData, Character } from '@/services/api';
import styles from './dashboard.module.css';

export default function DashboardPage() {
    const router = useRouter();
    const { isAuthenticated, isLoading, signOut } = useAuth();
    const [dashboard, setDashboard] = useState<DashboardData | null>(null);
    const [characters, setCharacters] = useState<Character[]>([]);
    const [selectedChar, setSelectedChar] = useState<Character | null>(null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            router.push('/login');
            return;
        }

        if (isAuthenticated) {
            loadData();
        }
    }, [isAuthenticated, isLoading, router]);

    const loadData = async () => {
        try {
            const [dashData, charData] = await Promise.all([
                api.getDashboard(),
                api.getCharacters()
            ]);
            setDashboard(dashData);
            setCharacters(charData);
            if (charData.length > 0) {
                setSelectedChar(charData[0]);
            }
        } catch (err: any) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleLogout = () => {
        signOut();
        router.push('/login');
    };

    if (isLoading || loading) {
        return (
            <div className={styles.loading}>
                <div className="spinner"></div>
            </div>
        );
    }

    if (error) {
        return (
            <div className={styles.container}>
                <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
                    <h2>Hata</h2>
                    <p style={{ color: '#ef4444', marginTop: '12px' }}>{error}</p>
                    <button className="btn btn-primary" onClick={handleLogout} style={{ marginTop: '20px' }}>
                        Çıkış Yap
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className={styles.container}>
            {/* Header with Character Selector */}
            <header className={styles.header}>
                <div className={styles.logo}>⚔️ Metin2 Panel</div>

                {characters.length > 1 && (
                    <div className={styles.charSelector}>
                        <label>Karakter:</label>
                        <select
                            value={selectedChar?.id || ''}
                            onChange={(e) => {
                                const char = characters.find(c => c.id === parseInt(e.target.value));
                                setSelectedChar(char || null);
                            }}
                            className={styles.select}
                        >
                            {characters.map(char => (
                                <option key={char.id} value={char.id}>
                                    {char.name} (Lv.{char.level})
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                <button className="btn btn-primary" onClick={handleLogout}>Çıkış</button>
            </header>

            {/* Selected Character Card */}
            {selectedChar && (
                <div className={`card ${styles.mainCard}`}>
                    <div className={styles.charInfo}>
                        <span className={styles.charIcon}>🧙</span>
                        <div>
                            <h2>{selectedChar.name}</h2>
                            <p>{selectedChar.job_name} • Lv.{selectedChar.level}</p>
                        </div>
                    </div>
                    <div className={styles.expBar}>
                        <div className="progress-bar">
                            <div className="progress-fill" style={{ width: `${selectedChar.exp_percent}%` }}></div>
                        </div>
                        <span>{selectedChar.exp_percent}%</span>
                    </div>
                    <p className={styles.gold}>💰 {selectedChar.gold_formatted}</p>
                </div>
            )}

            {/* Quick Stats */}
            <div className={`grid-4 ${styles.statsGrid}`}>
                <div className="card stat-card">
                    <span className="stat-value">{characters.length}</span>
                    <span className="stat-label">Karakter</span>
                </div>
                <div className="card stat-card">
                    <span className="stat-value">{dashboard?.quick_stats?.items_in_shop || 0}</span>
                    <span className="stat-label">Pazardaki Eşya</span>
                </div>
                <div className="card stat-card">
                    <span className="stat-value">{dashboard?.quick_stats?.available_dungeons || 0}</span>
                    <span className="stat-label">Müsait Zindan</span>
                </div>
                <div className="card stat-card">
                    <span className="stat-value">{dashboard?.quick_stats?.active_events_count || 0}</span>
                    <span className="stat-label">Aktif Etkinlik</span>
                </div>
            </div>

            {/* Bugün Ne Yapmalıyım? */}
            <section className={styles.section}>
                <h2 className={styles.sectionTitle}>📋 Bugün Ne Yapmalıyım?</h2>
                <div className={styles.todoList}>
                    {dashboard?.todo_list?.length ? (
                        dashboard.todo_list.map((todo, idx) => (
                            <div key={idx} className={`todo-item todo-${todo.priority}`}>
                                <span className="todo-icon">{todo.icon}</span>
                                <div className="todo-content">
                                    <div className="todo-title">{todo.title}</div>
                                    <div className="todo-desc">{todo.description}</div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className={styles.empty}>Bugün yapılacak bir şey yok! 🎉</div>
                    )}
                </div>
            </section>

            {/* Biologist & Dungeons */}
            <div className="grid-2">
                <section className="card">
                    <h3 className={styles.cardTitle}>🧪 Biyolog Durumu</h3>
                    {dashboard?.biologist?.enabled ? (
                        <div>
                            <p><strong>Aşama:</strong> {dashboard.biologist.stage_name}</p>
                            <p>
                                <strong>Durum:</strong>{' '}
                                {dashboard.biologist.can_deliver ? (
                                    <span className="badge badge-success">✅ Teslimat Hazır</span>
                                ) : (
                                    <span className="badge badge-warning">⏳ {dashboard.biologist.remaining_formatted}</span>
                                )}
                            </p>
                        </div>
                    ) : (
                        <p className={styles.muted}>Biyolog verisi bulunamadı</p>
                    )}
                </section>

                <section className="card">
                    <h3 className={styles.cardTitle}>⚔️ Zindanlar</h3>
                    {dashboard?.dungeons?.length ? (
                        <div className={styles.dungeonList}>
                            {dashboard.dungeons.map((d, idx) => (
                                <div key={idx} className={styles.dungeonItem}>
                                    <span>{d.name}</span>
                                    <span className={d.available ? 'badge badge-success' : 'badge badge-danger'}>
                                        {d.status}
                                    </span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className={styles.muted}>Zindan verisi bulunamadı</p>
                    )}
                </section>
            </div>

            {/* Active Events */}
            {dashboard?.active_events && dashboard.active_events.length > 0 && (
                <section className={styles.section}>
                    <h2 className={styles.sectionTitle}>🔥 Aktif Etkinlikler</h2>
                    <div className="grid-2">
                        {dashboard.active_events.map((event, idx) => (
                            <div key={idx} className="card">
                                <h4>{event.name}</h4>
                                <p className={styles.muted}>{event.description}</p>
                                {event.remaining && <span className="badge badge-warning">⏳ {event.remaining}</span>}
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {/* Shop Summary */}
            {dashboard?.shop_summary?.has_shop && (
                <section className="card">
                    <h3 className={styles.cardTitle}>🏪 Pazar Durumu</h3>
                    <p><strong>Pazar:</strong> {dashboard.shop_summary.shop_name || 'Pazarım'}</p>
                    <p><strong>Eşya Sayısı:</strong> {dashboard.shop_summary.total_items}</p>
                    <p><strong>Toplam Değer:</strong> {dashboard.shop_summary.total_value}</p>
                </section>
            )}
        </div>
    );
}

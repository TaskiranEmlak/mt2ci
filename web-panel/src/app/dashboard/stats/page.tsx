'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { Character, StatisticsData } from '@/services/api';
import styles from './stats.module.css';

export default function StatisticsPage() {
    const router = useRouter();
    const { isAuthenticated, isLoading } = useAuth();
    const [characters, setCharacters] = useState<Character[]>([]);
    const [selectedCharId, setSelectedCharId] = useState<number>(0);
    const [stats, setStats] = useState<StatisticsData | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            router.push('/login');
            return;
        }

        if (isAuthenticated) {
            loadCharacters();
        }
    }, [isAuthenticated, isLoading, router]);

    const loadCharacters = async () => {
        try {
            const data = await api.getCharacters();
            setCharacters(data);
            if (data.length > 0) {
                setSelectedCharId(data[0].id);
                loadStats(data[0].id);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const loadStats = async (charId: number) => {
        try {
            const statsData = await api.getStatistics(charId);
            setStats(statsData);
        } catch (err) {
            console.error(err);
        }
    };

    const handleCharChange = (charId: number) => {
        setSelectedCharId(charId);
        loadStats(charId);
    };

    if (isLoading || loading) {
        return <div className={styles.loading}><div className="spinner"></div></div>;
    }

    if (!stats) {
        return <div className={styles.container}>Veri yükleniyor...</div>;
    }

    return (
        <div className={styles.container}>
            {characters.length > 1 && (
                <div className={styles.charSelector}>
                    <label>Karakter:</label>
                    <select value={selectedCharId} onChange={(e) => handleCharChange(Number(e.target.value))}>
                        {characters.map(char => (
                            <option key={char.id} value={char.id}>{char.name}</option>
                        ))}
                    </select>
                </div>
            )}

            <h1 className={styles.title}>📊 İstatistikler</h1>

            <div className="grid-4">
                <div className="card stat-card">
                    <span className="stat-value">{stats.playtime.total_days}</span>
                    <span className="stat-label">Toplam Gün</span>
                </div>
                <div className="card stat-card">
                    <span className="stat-value">{stats.playtime.total_hours}</span>
                    <span className="stat-label">Toplam Saat</span>
                </div>
                <div className="card stat-card">
                    <span className="stat-value">{stats.refine.success_rate}%</span>
                    <span className="stat-label">Upgrade Başarı</span>
                </div>
                <div className="card stat-card">
                    <span className="stat-value">{stats.fishing.total_fish_caught}</span>
                    <span className="stat-label">Balık Tutma</span>
                </div>
            </div>

            <div className="grid-2" style={{ marginTop: '24px' }}>
                <div className="card">
                    <h2>💰 Yang İstatistikleri</h2>
                    <div className={styles.goldStats}>
                        <div className={styles.goldItem}>
                            <span className="text-muted">Kazanılan</span>
                            <span className={styles.goldPositive}>{stats.gold.total_earned_formatted}</span>
                        </div>
                        <div className={styles.goldItem}>
                            <span className="text-muted">Harcanan</span>
                            <span className={styles.goldNegative}>{stats.gold.total_spent_formatted}</span>
                        </div>
                        <div className={styles.goldItem}>
                            <span className="text-muted">Net</span>
                            <span className={styles.goldNet}>{stats.gold.net_formatted}</span>
                        </div>
                    </div>
                </div>

                <div className="card">
                    <h2>🔨 Upgrade İstatistikleri</h2>
                    <div className={styles.refineStats}>
                        <div className={styles.refineItem}>
                            <span className="badge badge-info">Toplam: {stats.refine.total_attempts}</span>
                            <span className="badge badge-success">Başarılı: {stats.refine.successful}</span>
                            <span className="badge badge-danger">Başarısız: {stats.refine.failed}</span>
                        </div>
                        <div className="progress-bar" style={{ marginTop: '16px' }}>
                            <div className="progress-fill" style={{ width: `${stats.refine.success_rate}%` }}></div>
                        </div>
                        <p className="text-center text-muted" style={{ marginTop: '8px' }}>
                            Başarı Oranı: {stats.refine.success_rate}%
                        </p>
                    </div>
                </div>
            </div>

            {stats.level_progression.length > 0 && (
                <div className="card" style={{ marginTop: '24px' }}>
                    <h2>📈 Level Geçmişi</h2>
                    <div className={styles.levelHistory}>
                        {stats.level_progression.slice(0, 10).map((lv, idx) => (
                            <div key={idx} className={styles.levelItem}>
                                <span className={styles.levelBadge}>Lv.{lv.level}</span>
                                <span className="text-muted">{lv.date}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { RankingData } from '@/services/api';
import styles from './ranking.module.css';

export default function RankingPage() {
    const router = useRouter();
    const { isAuthenticated, isLoading } = useAuth();
    const [rankingType, setRankingType] = useState<'level' | 'gold' | 'alignment'>('level');
    const [ranking, setRanking] = useState<RankingData[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            router.push('/login');
            return;
        }

        if (isAuthenticated) {
            loadRanking();
        }
    }, [isAuthenticated, isLoading, router, rankingType]);

    const loadRanking = async () => {
        setLoading(true);
        try {
            const data = await api.getRanking(rankingType);
            setRanking(data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (isLoading || loading) {
        return <div className={styles.loading}><div className="spinner"></div></div>;
    }

    return (
        <div className={styles.container}>
            <h1 className={styles.title}>🏆 Sıralama</h1>

            {/* Tabs */}
            <div className={styles.tabs}>
                <button
                    className={rankingType === 'level' ? styles.tabActive : styles.tab}
                    onClick={() => setRankingType('level')}
                >
                    📊 Seviye
                </button>
                <button
                    className={rankingType === 'gold' ? styles.tabActive : styles.tab}
                    onClick={() => setRankingType('gold')}
                >
                    💰 Zenginlik
                </button>
                <button
                    className={rankingType === 'alignment' ? styles.tabActive : styles.tab}
                    onClick={() => setRankingType('alignment')}
                >
                    ⭐ Kahraman
                </button>
            </div>

            {/* Ranking Table */}
            <div className="card">
                <table className={styles.table}>
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>İsim</th>
                            <th>Seviye</th>
                            <th>Sınıf</th>
                            <th>İmparatorluk</th>
                            {rankingType === 'gold' && <th>Altın</th>}
                            {rankingType === 'alignment' && <th>Puan</th>}
                            {rankingType === 'alignment' && <th>Rütbe</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {ranking.map((player) => (
                            <tr key={player.rank} className={styles.row}>
                                <td className={styles.rank}>
                                    {player.rank <= 3 ? (
                                        <span className={styles.medal}>
                                            {player.rank === 1 ? '🥇' : player.rank === 2 ? '🥈' : '🥉'}
                                        </span>
                                    ) : (
                                        player.rank
                                    )}
                                </td>
                                <td className={styles.name}>{player.name}</td>
                                <td>{player.level}</td>
                                <td>{player.job}</td>
                                <td>{player.empire}</td>
                                {rankingType === 'gold' && <td>{player.gold_formatted}</td>}
                                {rankingType === 'alignment' && <td>{player.alignment?.toLocaleString()}</td>}
                                {rankingType === 'alignment' && <td><span className="badge badge-success">{player.alignment_rank}</span></td>}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

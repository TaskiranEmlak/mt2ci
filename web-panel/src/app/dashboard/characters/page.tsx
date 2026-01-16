'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { Character } from '@/services/api';
import styles from './characters.module.css';

export default function CharactersPage() {
    const router = useRouter();
    const { isAuthenticated, is Loading } = useAuth();
    const [characters,

        setCharacters] = useState<Character[]>([]);
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
            <h1 className={styles.title}>🧙 Karakterlerim</h1>

            <div className={styles.grid}>
                {characters.map((char) => (
                    <div key={char.id} className="card">
                        <div className={styles.charHeader}>
                            <span className={styles.charIcon}>🧙</span>
                            <div>
                                <h3>{char.name}</h3>
                                <p className={styles.job}>{char.job_name}</p>
                            </div>
                        </div>

                        <div className={styles.stats}>
                            <div className={styles.stat}>
                                <span className={styles.label}>Seviye</span>
                                <span className={styles.value}>{char.level}</span>
                            </div>
                            <div className={styles.stat}>
                                <span className={styles.label}>Tecrübe</span>
                                <span className={styles.value}>{char.exp_percent}%</span>
                            </div>
                            <div className={styles.stat}>
                                <span className={styles.label}>Yang</span>
                                <span className={styles.value}>{char.gold_formatted}</span>
                            </div>
                            <div className={styles.stat}>
                                <span className={styles.label}>Oynama</span>
                                <span className={styles.value}>{char.playtime_formatted}</span>
                            </div>
                        </div>

                        <div className={styles.expBar}>
                            <div className="progress-bar">
                                <div className="progress-fill" style={{ width: `${char.exp_percent}%` }}></div>
                            </div>
                        </div>

                        <div className={styles.badges}>
                            <span className="badge badge-success">{char.alignment_rank}</span>
                            {char.champion_level > 0 && (
                                <span className="badge badge-warning">Şampiyon Lv.{char.champion_level}</span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

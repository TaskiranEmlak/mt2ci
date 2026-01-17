'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { Character, SocialData } from '@/services/api';
import styles from './social.module.css';

export default function SocialPage() {
    const router = useRouter();
    const { isAuthenticated, isLoading } = useAuth();
    const [characters, setCharacters] = useState<Character[]>([]);
    const [selectedCharId, setSelectedCharId] = useState<number>(0);
    const [social, setSocial] = useState<SocialData | null>(null);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState<'marriage' | 'friends'>('marriage');

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
                loadSocial(data[0].id);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const loadSocial = async (charId: number) => {
        try {
            const account = localStorage.getItem('account_login') || '';
            const socialData = await api.getSocial(charId, account);
            setSocial(socialData);
        } catch (err) {
            console.error(err);
        }
    };

    const handleCharChange = (charId: number) => {
        setSelectedCharId(charId);
        loadSocial(charId);
    };

    if (isLoading || loading) {
        return <div className={styles.loading}><div className="spinner"></div></div>;
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

            <h1 className={styles.title}>👥 Sosyal</h1>

            <div className={styles.tabs}>
                <button
                    className={activeTab === 'marriage' ? styles.tabActive : styles.tab}
                    onClick={() => setActiveTab('marriage')}
                >
                    💍 Evlilik
                </button>
                <button
                    className={activeTab === 'friends' ? styles.tabActive : styles.tab}
                    onClick={() => setActiveTab('friends')}
                >
                    👥 Arkadaşlar
                </button>
            </div>

            {activeTab === 'marriage' && (
                <div className="card">
                    {social?.marriage?.is_married ? (
                        <div className={styles.marriageInfo}>
                            <div className={styles.marriageHeader}>
                                <h2>💕 Evlisiniz!</h2>
                                <span className="badge badge-success">Aktif</span>
                            </div>
                            <div className={styles.partnerCard}>
                                <div className={styles.partnerInfo}>
                                    <h3>{social.marriage.partner_name}</h3>
                                    <p>{social.marriage.partner_job} • Lv.{social.marriage.partner_level}</p>
                                </div>
                                <div className={styles.lovePoints}>
                                    <span className="stat-value">{social.marriage.love_point}</span>
                                    <span className="stat-label">Sevgi Puanı</span>
                                </div>
                            </div>
                            <div className={styles.marriageStats}>
                                <div>
                                    <p className="text-muted">Evlilik Tarihi</p>
                                    <p>{social.marriage.married_since}</p>
                                </div>
                                <div>
                                    <p className="text-muted">Süre</p>
                                    <p>{social.marriage.duration_days} Gün</p>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className={styles.empty}>
                            <h2>💔 Evli Değilsiniz</h2>
                            <p>Şu anda evli değilsiniz.</p>
                        </div>
                    )}
                </div>
            )}

            {activeTab === 'friends' && (
                <div className="card">
                    {social?.friends && social.friends.length > 0 ? (
                        <div className={styles.friendsList}>
                            {social.friends.map((friend, idx) => (
                                <div key={idx} className={styles.friendCard}>
                                    <div className={styles.friendInfo}>
                                        <span className={styles.friendIcon}>👤</span>
                                        <span>{friend.account}</span>
                                    </div>
                                    <span className={`badge ${friend.status === 'online' ? 'badge-success' : 'badge-danger'}`}>
                                        {friend.status === 'online' ? 'Çevrimiçi' : 'Çevrimdışı'}
                                    </span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className={styles.empty}>
                            <h2>👥 Arkadaş Yok</h2>
                            <p>Arkadaş listeniz boş.</p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

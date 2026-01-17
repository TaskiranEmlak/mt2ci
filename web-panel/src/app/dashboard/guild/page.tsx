'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { Character, GuildData, GuildMember } from '@/services/api';
import styles from './guild.module.css';

export default function GuildPage() {
    const router = useRouter();
    const { isAuthenticated, isLoading } = useAuth();
    const [characters, setCharacters] = useState<Character[]>([]);
    const [selectedCharId, setSelectedCharId] = useState<number>(0);
    const [guild, setGuild] = useState<GuildData | null>(null);
    const [members, setMembers] = useState<GuildMember[]>([]);
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
                loadGuild(data[0].id);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const loadGuild = async (charId: number) => {
        try {
            const guildData = await api.getGuild(charId);
            setGuild(guildData);

            if (guildData.has_guild && guildData.guild_id) {
                const memberData = await api.getGuildMembers(guildData.guild_id);
                setMembers(memberData);
            }
        } catch (err) {
            console.error(err);
        }
    };

    const handleCharChange = (charId: number) => {
        setSelectedCharId(charId);
        loadGuild(charId);
    };

    if (isLoading || loading) {
        return <div className={styles.loading}><div className="spinner"></div></div>;
    }

    if (!guild || !guild.has_guild) {
        return (
            <div className={styles.container}>
                <div className={styles.empty}>
                    <h2>🏰 Loncanız Yok</h2>
                    <p>Şu anda bir loncaya üye değilsiniz.</p>
                </div>
            </div>
        );
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

            <div className={`card card-gradient ${styles.guildHeader}`}>
                <div className={styles.guildInfo}>
                    <h1 className="text-gradient">{guild.name}</h1>
                    <p className="text-muted">Lider: {guild.master_name}</p>
                </div>
                <div className={styles.guildStats}>
                    <div className="stat-card">
                        <span className="stat-value">{guild.level}</span>
                        <span className="stat-label">Seviye</span>
                    </div>
                    <div className="stat-card">
                        <span className="stat-value">{guild.member_count}</span>
                        <span className="stat-label">Üye</span>
                    </div>
                    <div className="stat-card">
                        <span className="stat-value">{guild.ladder_point}</span>
                        <span className="stat-label">Puan</span>
                    </div>
                </div>
            </div>

            <div className="grid-3" style={{ marginTop: '24px' }}>
                <div className="card">
                    <h3>💰 Lonca Hazinesi</h3>
                    <p className={styles.goldAmount}>{guild.gold_formatted}</p>
                </div>
                <div className="card">
                    <h3>⚔️ Savaş İstatistikleri</h3>
                    <div className={styles.warStats}>
                        <span className="badge badge-success">Galibiyet: {guild.win}</span>
                        <span className="badge badge-warning">Berabere: {guild.draw}</span>
                        <span className="badge badge-danger">Mağlubiyet: {guild.loss}</span>
                    </div>
                </div>
                <div className="card">
                    <h3>👤 Rütbeniz</h3>
                    <p className={styles.gradeInfo}>
                        {guild.is_general && <span className="badge badge-info">🎖️ General</span>}
                        <span>Derece: {guild.player_grade}</span>
                    </p>
                </div>
            </div>

            <div className="card" style={{ marginTop: '24px' }}>
                <h2>👥 Lonca Üyeleri ({members.length})</h2>
                <div className={styles.membersTable}>
                    <table>
                        <thead>
                            <tr>
                                <th>İsim</th>
                                <th>Seviye</th>
                                <th>Meslek</th>
                                <th>Rütbe</th>
                            </tr>
                        </thead>
                        <tbody>
                            {members.map((member, idx) => (
                                <tr key={idx}>
                                    <td>
                                        {member.name}
                                        {member.is_general && <span className="badge badge-info" style={{ marginLeft: '8px' }}>General</span>}
                                    </td>
                                    <td>{member.level}</td>
                                    <td>{member.job}</td>
                                    <td>{member.grade_name}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

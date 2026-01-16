'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { Character, Conversation } from '@/services/api';
import styles from './messages.module.css';

export default function MessagesPage() {
    const router = useRouter();
    const { isAuthenticated, isLoading } = useAuth();
    const [characters, setCharacters] = useState<Character[]>([]);
    const [selectedChar, setSelectedChar] = useState<string>('');
    const [conversations, setConversations] = useState<Conversation[]>([]);
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
                setSelectedChar(data[0].name);
                loadMessages(data[0].name);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const loadMessages = async (charName: string) => {
        try {
            const data = await api.getMessages(charName);
            setConversations(data);
        } catch (err) {
            console.error(err);
        }
    };

    const handleCharChange = (charName: string) => {
        setSelectedChar(charName);
        loadMessages(charName);
    };

    if (isLoading || loading) {
        return <div className={styles.loading}><div className="spinner"></div></div>;
    }

    return (
        <div className={styles.container}>
            <h1 className={styles.title}>💬 Mesajlarım</h1>

            {characters.length > 1 && (
                <div className={styles.charSelector}>
                    <label>Karakter:</label>
                    <select value={selectedChar} onChange={(e) => handleCharChange(e.target.value)} className={styles.select}>
                        {characters.map(char => (
                            <option key={char.id} value={char.name}>{char.name}</option>
                        ))}
                    </select>
                </div>
            )}

            {conversations.length > 0 ? (
                <div className={styles.conversations}>
                    {conversations.map((conv, idx) => (
                        <div key={idx} className="card">
                            <div className={styles.convHeader}>
                                <h3>💬 {conv.contact}</h3>
                                <span className={styles.time}>{conv.last_time}</span>
                            </div>
                            <p className={styles.lastMessage}>{conv.last_message}</p>
                            <div className={styles.messages}>
                                {conv.messages.slice(-5).map((msg, msgIdx) => (
                                    <div key={msgIdx} className={msg.is_mine ? styles.myMessage : styles.theirMessage}>
                                        <p>{msg.content}</p>
                                        <span className={styles.msgTime}>{msg.time}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className={styles.empty}>
                    <p>Henüz mesaj geçmişi yok.</p>
                </div>
            )}
        </div>
    );
}

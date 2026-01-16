'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { EventsData } from '@/services/api';
import styles from './events.module.css';

export default function EventsPage() {
    const router = useRouter();
    const { isAuthenticated, isLoading } = useAuth();
    const [events, setEvents] = useState<EventsData | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            router.push('/login');
            return;
        }

        if (isAuthenticated) {
            loadEvents();
        }
    }, [isAuthenticated, isLoading, router]);

    const loadEvents = async () => {
        try {
            const data = await api.getEvents();
            setEvents(data);
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
            <h1 className={styles.title}>🔥 Etkinlikler</h1>

            {events?.active && events.active.length > 0 && (
                <section className={styles.section}>
                    <h2 className={styles.sectionTitle}>Aktif Etkinlikler</h2>
                    <div className={styles.grid}>
                        {events.active.map((event, idx) => (
                            <div key={idx} className={`card ${styles.eventCard}`}>
                                <div className={styles.eventHeader}>
                                    <h3>🔥 {event.name}</h3>
                                    <span className="badge badge-success">Aktif</span>
                                </div>
                                <p className={styles.description}>{event.description}</p>
                                {event.remaining && (
                                    <p className={styles.remaining}>⏳ Kalan: {event.remaining}</p>
                                )}
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {events?.upcoming && events.upcoming.length > 0 && (
                <section className={styles.section}>
                    <h2 className={styles.sectionTitle}>Yaklaşan Etkinlikler</h2>
                    <div className={styles.grid}>
                        {events.upcoming.map((event, idx) => (
                            <div key={idx} className="card">
                                <div className={styles.eventHeader}>
                                    <h3>📅 {event.name}</h3>
                                    <span className="badge badge-warning">Yakında</span>
                                </div>
                                <p className={styles.description}>{event.description}</p>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {(!events?.active || events.active.length === 0) && (!events?.upcoming || events.upcoming.length === 0) && (
                <div className={styles.empty}>
                    <p>Şu anda aktif veya yaklaşan etkinlik yok.</p>
                </div>
            )}
        </div>
    );
}

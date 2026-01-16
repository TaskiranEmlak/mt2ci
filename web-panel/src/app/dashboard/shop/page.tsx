'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import api, { ShopData } from '@/services/api';
import styles from './shop.module.css';

export default function ShopPage() {
    const router = useRouter();
    const { isAuthenticated, isLoading } = useAuth();
    const [shop, setShop] = useState<ShopData | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            router.push('/login');
            return;
        }

        if (isAuthenticated) {
            loadShop();
        }
    }, [isAuthenticated, isLoading, router]);

    const loadShop = async () => {
        try {
            const data = await api.getShop();
            setShop(data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (isLoading || loading) {
        return <div className={styles.loading}><div className="spinner"></div></div>;
    }

    if (!shop || !shop.shop_name) {
        return (
            <div className={styles.container}>
                <div className={styles.empty}>
                    <h2>🏪 Pazarınız Yok</h2>
                    <p>Şu anda aktif bir pazarınız bulunmuyor.</p>
                </div>
            </div>
        );
    }

    return (
        <div className={styles.container}>
            <div className={styles.header}>
                <div>
                    <h1 className={styles.title}>🏪 {shop.shop_name}</h1>
                    <p className={styles.subtitle}>Toplam {shop.total_items} eşya • {shop.total_value}</p>
                </div>
            </div>

            {shop.items && shop.items.length > 0 && (
                <div className={styles.itemsGrid}>
                    {shop.items.map((item, idx) => (
                        <div key={idx} className="card">
                            <div className={styles.itemHeader}>
                                <h3>📦 {item.name}</h3>
                                <span className="badge badge-success">{item.price_formatted}</span>
                            </div>
                            <div className={styles.itemDetails}>
                                <p><strong>Adet:</strong> {item.count}</p>
                                {item.attributes && item.attributes.length > 0 && (
                                    <div className={styles.attributes}>
                                        {item.attributes.map((attr, attrIdx) => (
                                            <span key={attrIdx} className={styles.attribute}>
                                                {attr.name}: +{attr.value}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

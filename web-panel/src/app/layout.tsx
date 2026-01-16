import './globals.css';
import { AuthProvider } from '@/contexts/AuthContext';

export const metadata = {
    title: 'Metin2 Panel',
    description: 'Metin2 Web Panel - Karakterini Yönet',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
    return (
        <html lang="tr">
            <body>
                <AuthProvider>
                    {children}
                </AuthProvider>
            </body>
        </html>
    );
}

// (home)/layout.tsx
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import WhatsAppButton from '@/components/WhatsAppButton';

export default function HomeLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <>
            <Header />
            <main className="bg-light">{children}</main>
            <Footer />
            <WhatsAppButton />
        </>
    );
}
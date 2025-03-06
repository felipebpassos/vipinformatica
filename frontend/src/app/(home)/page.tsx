import HeroSection from '@/components/home/HeroSection'
import ServicesSection from '@/components/home/ServicesSection'
import ClientsSection from '@/components/home/ClientsSection'
import CTA from '@/components/home/CTA'

export default function Home() {
    return (
        <>
            <HeroSection />
            <ServicesSection />
            <ClientsSection />
            <CTA />
        </>
    )
}
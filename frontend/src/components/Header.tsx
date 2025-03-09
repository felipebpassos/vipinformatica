import Link from 'next/link'
import Image from 'next/image'

export default function Header() {
    return (
        <header className="bg-white shadow sticky top-0 z-50">
            <nav className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between h-16 items-center">
                    <Link href="/">
                        <Image 
                            src="/logo.png" 
                            alt="Vip Informática Logo" 
                            width={150} 
                            height={50} 
                            priority 
                        />
                    </Link>
                    
                    {/* Links de navegação */}
                    <div className="flex items-center gap-0">
                        <div className="flex gap-3">
                            <Link 
                                href="#servicos" 
                                className="px-4 py-2 rounded-md hover:bg-gray-200 active:bg-gray-300 transition text-gray-600"
                            >
                                Serviços
                            </Link>
                            <Link 
                                href="/portfolio" 
                                className="px-4 py-2 rounded-md hover:bg-gray-200 active:bg-gray-300 transition text-gray-600"
                            >
                                Portfolio
                            </Link>
                            <Link 
                                href="#footer" 
                                className="px-4 py-2 rounded-md hover:bg-gray-200 active:bg-gray-300 transition text-gray-600"
                            >
                                Contato
                            </Link>
                        </div>
                        
                        <div className="flex gap-4 ml-8">
                            <Link 
                                href="/login" 
                                className="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-900 transition"
                            >
                                Minha conta
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>
        </header>
    )
}
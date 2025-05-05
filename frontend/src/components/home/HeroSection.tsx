import Link from 'next/link';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faHeadset } from '@fortawesome/free-solid-svg-icons';

export default function HeroSection() {
    return (
        <section
            className="relative text-white px-4 bg-cover bg-center flex items-center justify-center min-h-[max(600px,70vh)]"
            style={{ backgroundImage: "url('/technology_office.webp')" }}
        >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/70 z-0" />

            <div className="max-w-6xl mx-auto text-center p-8 rounded-lg relative z-10">
                <h1 className="text-3xl md:text-5xl lg:text-6xl xl:text-7xl font-bold mb-6 leading-tight">
                    Soluções em Tecnologia para Você e para sua Empresa
                </h1>
                <p className="max-w-4xl text-base md:text-lg lg:text-xl xl:text-2xl mb-12 mx-auto text-gray-300 leading-relaxed">
                    Mais de 20 anos em TI: desde suporte técnico, a desenvolvimento web e infraestrutura de redes.
                </p>

                {/* Container principal alinhado ao centro */}
                <div className="flex flex-col m-auto w-fit py-2 px-4 md:py-3 md:px-5 bg-white/5 rounded-md text-sm md:text-base">

                    {/* Box do ícone + texto alinhado à esquerda */}
                    <div className="mb-1 md:mb-2">
                        <div className="flex items-center space-x-1 md:space-x-2 justify-start">
                            <FontAwesomeIcon
                                icon={faHeadset}
                                className="text-sm md:text-base text-gray-400"
                            />
                            <span className="text-xs md:text-sm text-gray-400">Fale com um especialista</span>
                        </div>
                    </div>

                    {/* Botão */}
                    <Link
                        href="/chat"
                        className="styled-button-red text-sm md:text-base py-2 px-4 md:py-3 md:px-6"
                    >
                        Precisando de suporte?
                    </Link>
                </div>
            </div>
        </section>
    )
}

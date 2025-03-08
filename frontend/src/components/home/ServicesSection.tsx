import { services } from '@/lib/constants'
import { Transition } from '@headlessui/react'
import SectionTitle from './SectionTitle' 
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

export default function ServicesSection() {
    return (
        <section id="servicos" className="py-16">
            <div className="max-w-7xl mx-auto px-4">
                
                <SectionTitle
                    subtitle="Serviços"
                    title="O que fazemos?"
                />

                <div className="grid md:grid-cols-3 gap-8">
                    {services.map((service, index) => (
                        <Transition
                            key={service.title}
                            appear={true}
                            show={true}
                            enter="transition-opacity duration-500"
                            enterFrom="opacity-0"
                            enterTo="opacity-100"
                        >
                            <div className="p-8 bg-white rounded-2xl cursor-pointer hover:bg-gray-900 hover:text-white transition-colors relative h-84">
                                <div className="text-gray-600 text-5xl mb-6">
                                    <FontAwesomeIcon icon={service.icon} />
                                </div>
                                <h3 className="text-xl font-semibold mb-2">{service.title}</h3>
                                <p className="opacity-60">{service.description}</p>
                                <div className="absolute bottom-6 left-6 w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                    <svg
                                        className="w-6 h-6 text-gray-700"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M9 5l7 7-7 7"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                        </Transition>
                    ))}
                </div>
            </div>
        </section>
    )
}
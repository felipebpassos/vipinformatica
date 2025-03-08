import { 
  faDesktop,
  faLaptopCode,
  faServer,
  faMobileAlt,
  faCog,
  faFingerprint
} from '@fortawesome/free-solid-svg-icons';

// Ícones podem ser substituídos por SVG ou componentes de biblioteca
export const services = [
    {
        icon: faDesktop,
        title: 'Suporte Técnico',
        description: 'Manutenção de computadores e notebooks, incluindo formatação, instalação de programas e equipamentos digitais.'
    },
    {
        icon: faLaptopCode,
        title: 'Criação de Sites',
        description: 'Desenvolvimento de sites responsivos, rápidos e otimizados para SEO, garantindo uma presença digital profissional.'
    },
    {
        icon: faServer,
        title: 'Consultoria em TI',
        description: 'Instalação e manutenção de servidores, redes e sistemas, garantindo infraestrutura segura e de alto desempenho para sua empresa.'
    },
    {
        icon: faMobileAlt,
        title: 'Desenvolvimento de Aplicativos',
        description: 'Aplicações móveis Android e iOS sob medida, focadas em usabilidade e funcionalidade.'
    },
    {
        icon: faCog,
        title: 'Sistemas Web',
        description: 'Desenvolvimento e integração de sistemas web para otimizar processos, automatizar tarefas e conectar diferentes plataformas de forma eficiente.'
    },    
    {
        icon: faFingerprint,
        title: 'Segurança Digital',
        description: 'Implementação de soluções de segurança, monitoramento de ameaças e proteção contra ataques cibernéticos.'
    }
]

export const clients = [
    {
        logo: '/images/clients/empresa1.png',
        alt: 'Logo Empresa 1'
    },
    {
        logo: '/images/clients/empresa2.png',
        alt: 'Logo Empresa 2'
    },
    // Adicione mais clientes...
]
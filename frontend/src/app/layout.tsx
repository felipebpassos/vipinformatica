import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './styles/globals.css'
import './styles/WhatsAppBtn.css'
import './styles/chat.css'
import { config } from '@fortawesome/fontawesome-svg-core';
import '@fortawesome/fontawesome-svg-core/styles.css';

// Configuração para evitar estilos duplicados
config.autoAddCss = false;

const inter = Inter({ subsets: ['latin'] })

export const metadata: Metadata = {
  title: 'VIP Informática - Soluções em tecnologia e informática',
  description: 'Soluções em tecnologia e informática',
  icons: {
    icon: "/favicon.ico",
  },
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="pt-BR">
      <body className={inter.className}>
        {children}
      </body>
    </html>
  )
}
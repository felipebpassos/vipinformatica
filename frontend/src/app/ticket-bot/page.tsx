'use client'
import { useState } from 'react'
import ChatFlow from '@/components/ticket/ChatFlow'

export default function TicketPage() {
    const [step, setStep] = useState(1)

    return (
        <div className="max-w-3xl mx-auto p-6">
            <h1 className="text-3xl font-bold mb-8">Assistente de Chamados</h1>
            <ChatFlow step={step} setStep={setStep} />
        </div>
    )
}
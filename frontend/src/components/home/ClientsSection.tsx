// ClientsSection.tsx
"use client"
import { useState, useRef, useEffect } from 'react'
import Image from 'next/image'
import { clients } from '@/lib/constants'
import SectionTitle from './SectionTitle'

export default function ClientsSection() {
    const [speedTop, setSpeedTop] = useState(1)
    const [speedBottom, setSpeedBottom] = useState(-1)
    const animationRefs = useRef<Animation[]>([])
    const isDragging = useRef(false)
    const startX = useRef(0)
    const currentSpeed = useRef({ top: 1, bottom: -1 })
    const activeLine = useRef<'top' | 'bottom' | null>(null)

    useEffect(() => {
        const elements = document.querySelectorAll('.marquee-content')

        elements.forEach((el, index) => {
            // Calcula a duração baseada na largura do conteúdo
            const contentWidth = el.scrollWidth
            const viewportWidth = window.innerWidth
            const duration = (contentWidth / viewportWidth) * 25000 

            const animation = el.animate(
                [
                    { transform: 'translateX(0%)' },
                    { transform: `translateX(-${contentWidth / 2}px)` }
                ],
                {
                    duration: duration,
                    iterations: Infinity
                }
            )
            animation.playbackRate = index === 0 ? speedTop : speedBottom
            animationRefs.current.push(animation)
        })

        return () => animationRefs.current.forEach(anim => anim.cancel())
    }, [])

    useEffect(() => {
        animationRefs.current[0] && (animationRefs.current[0].playbackRate = speedTop)
        animationRefs.current[1] && (animationRefs.current[1].playbackRate = speedBottom)
    }, [speedTop, speedBottom])

    const handleDragStart = (e: React.MouseEvent | React.TouchEvent) => {
        isDragging.current = true
        const target = e.target as HTMLElement
        const container = target.closest('.marquee-container')

        if (container) {
            activeLine.current = container.classList.contains('top-line') ? 'top' : 'bottom'
            startX.current = 'touches' in e ? e.touches[0].clientX : e.clientX
            currentSpeed.current = {
                top: speedTop,
                bottom: speedBottom
            }
        }

        document.addEventListener('mousemove', handleDrag)
        document.addEventListener('mouseup', handleDragEnd)
        document.addEventListener('touchmove', handleDrag)
        document.addEventListener('touchend', handleDragEnd)
    }

    const handleDrag = (e: MouseEvent | TouchEvent) => {
        if (!isDragging.current || !activeLine.current) return
        e.preventDefault()

        const currentX = 'touches' in e ? e.touches[0].clientX : (e as MouseEvent).clientX
        const delta = (currentX - startX.current) * 0.015

        if (activeLine.current === 'top') {
            const newSpeed = currentSpeed.current.top - delta
            setSpeedTop(Math.min(Math.max(newSpeed, -3), 3))
        } else {
            const newSpeed = currentSpeed.current.bottom - delta
            setSpeedBottom(Math.min(Math.max(newSpeed, -3), 3))
        }
    }

    const handleDragEnd = () => {
        isDragging.current = false
        activeLine.current = null
        document.removeEventListener('mousemove', handleDrag)
        document.removeEventListener('mouseup', handleDragEnd)
        document.removeEventListener('touchmove', handleDrag)
        document.removeEventListener('touchend', handleDragEnd)
    }

    return (
        <section className="py-16 overflow-hidden w-full">
            <div className="px-4 w-full">
                <SectionTitle
                    title="Parceiros que confiam em nosso trabalho"
                />

                <div
                    className="space-y-8 cursor-grab active:cursor-grabbing w-full"
                    onMouseDown={handleDragStart}
                    onTouchStart={handleDragStart}
                >
                    {/* Linha superior - Aumenta o número de cópias */}
                    <div className="marquee-container top-line overflow-hidden w-full">
                        <div className="marquee-content flex w-max gap-8">
                            {[...clients, ...clients, ...clients, ...clients].map((client, i) => (
                                <div key={`top-${i}`} className="relative h-20 w-40 flex-shrink-0 grayscale hover:grayscale-0 transition-all">
                                    <Image src={client.logo} alt={client.alt} fill className="object-contain" />
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Linha inferior - Aumenta o número de cópias */}
                    <div className="marquee-container bottom-line overflow-hidden w-full">
                        <div className="marquee-content flex w-max gap-8">
                            {[...clients, ...clients, ...clients, ...clients].map((client, i) => (
                                <div key={`bottom-${i}`} className="relative h-20 w-40 flex-shrink-0 grayscale hover:grayscale-0 transition-all">
                                    <Image src={client.logo} alt={client.alt} fill className="object-contain" />
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    )
}
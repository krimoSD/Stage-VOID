import { useState, useEffect, useRef } from "react"

export const config = {
    id: "capital_azur_dynamic_fields:ca_slider",
}

const resolveImageSrc = (src) => {
    if (!src) return null
    const url = typeof src === "string" ? src : (src._original || src[Object.keys(src)[0]] || null)
    if (!url) return null
    if (url.startsWith("http://") || url.startsWith("https://")) {
        try { return new URL(url).pathname } catch { return url }
    }
    return url
}

const SliderWidget = ({ data }) => {
    const [current, setCurrent] = useState(0)
    const [paused, setPaused] = useState(false)
    const intervalRef = useRef(null)

    const cfg = data?.extra_field?.group_gonfig || data?.extra_field?.group_config || {}
    const autoLoop = cfg?.autoLoop === 1 || cfg?.autoLoop === "1" || cfg?.autoLoop === true
    const loopSpeed = parseInt(cfg?.loopSpeed, 10) || 5000

    const slides = data?.components?.map((slide) => ({
        image: resolveImageSrc(slide?.image?.[0]?._default),
        imageAlt: slide?.image?.[0]?.meta?.alt || "",
        title: slide?.title,
        description: slide?.description,
    })) || []

    const total = slides.length

    const goTo = (index) => setCurrent((index + total) % total)
    const prev = () => goTo(current - 1)
    const next = () => goTo(current + 1)

    useEffect(() => {
        if (autoLoop && !paused && total > 1) {
            intervalRef.current = setInterval(() => goTo(current + 1), loopSpeed)
        }
        return () => clearInterval(intervalRef.current)
    }, [current, paused, autoLoop])

    if (total === 0) return null

    return (
        <div
            className="relative overflow-hidden w-full"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
        >
            <div
                className="flex transition-transform duration-700 ease-in-out"
                style={{ transform: `translateX(-${current * 100}%)` }}
            >
                {slides.map((slide, i) => (
                    <div key={i} className="relative min-w-full" style={{ minHeight: 420 }}>
                        {slide.image ? (
                            <img
                                src={slide.image}
                                alt={slide.imageAlt}
                                className="absolute inset-0 w-full h-full object-cover"
                            />
                        ) : (
                            <div className="absolute inset-0 bg-gradient-to-br from-[#0d1b3e] to-[#1a3a6e]" />
                        )}
                        <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />

                        <div className="relative z-10 flex items-end h-full min-h-[420px]">
                            <div className="w-full px-6 lg:px-10 pb-20">
                                <div className="max-w-lg">
                                    {slide.title && (
                                        <h2 className="text-3xl md:text-4xl font-extrabold text-white uppercase leading-tight mb-3">
                                            {slide.title}
                                        </h2>
                                    )}
                                    {slide.description && (
                                        <p className="text-white/80 text-base leading-relaxed">
                                            {slide.description}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {total > 1 && (
                <>
                    <button
                        onClick={prev}
                        className="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-colors"
                        aria-label="Previous slide"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        onClick={next}
                        className="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-colors"
                        aria-label="Next slide"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </>
            )}

            {total > 1 && (
                <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2">
                    {slides.map((_, i) => (
                        <button
                            key={i}
                            onClick={() => goTo(i)}
                            className={`h-2 rounded-full transition-all duration-300 ${i === current ? "bg-white w-6" : "bg-white/40 w-2 hover:bg-white/70"}`}
                            aria-label={`Go to slide ${i + 1}`}
                        />
                    ))}
                </div>
            )}
        </div>
    )
}

export default SliderWidget

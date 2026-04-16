"use client"
import { useState } from "react"

export const config = {
    id: "capital_azur_dynamic_fields:ca_accordion",
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

const PlusIcon = () => (
    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 5v14M5 12h14" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </svg>
)

const MinusIcon = () => (
    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M5 12h14" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </svg>
)

const AccordionItem = ({ item, isOpen, onToggle }) => {
    const imageSrc = resolveImageSrc(item?.image?.[0]?._default)
    const imageAlt = item?.image?.[0]?.meta?.alt || item?.title || ""
    const title = item?.title || ""
    const description = item?.description || ""
    const buttonLabel = item?.button_label || "EN SAVOIR PLUS"
    const buttonUrl = item?.button_url || "#"

    return (
        <div className="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
            {/* Header / Toggle */}
            <button
                type="button"
                onClick={onToggle}
                aria-expanded={isOpen}
                className="flex w-full items-center justify-between px-6 py-5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2b84e8]"
            >
                <span className={`text-base font-bold transition-colors ${isOpen ? "text-[#2b84e8]" : "text-[#0a1936]"}`}>
                    {title}
                </span>
                <span className={isOpen ? "text-[#2b84e8]" : "text-gray-400"}>
                    {isOpen ? <MinusIcon /> : <PlusIcon />}
                </span>
            </button>

            {/* Body */}
            {isOpen && (
                <div className="border-t border-gray-100">
                    <div className="flex flex-col sm:flex-row gap-6 p-6">
                        {/* Image */}
                        {imageSrc && (
                            <div className="shrink-0 w-full sm:w-[260px] lg:w-[300px] rounded-md overflow-hidden bg-gray-50 flex items-center justify-center">
                                <img
                                    src={imageSrc}
                                    alt={imageAlt}
                                    className="h-full w-full object-cover max-h-[200px] sm:max-h-none"
                                    loading="lazy"
                                />
                            </div>
                        )}

                        {/* Text content */}
                        <div className="flex flex-col justify-center gap-4">
                            {description && (
                                <div className="text-[15px] leading-relaxed text-gray-600 space-y-3 whitespace-pre-line">
                                    {description}
                                </div>
                            )}

                            {buttonLabel && (
                                <div className="mt-2">
                                    <a
                                        href={buttonUrl}
                                        className="inline-flex items-center gap-3 text-[13px] font-bold uppercase tracking-wider text-[#2b84e8] hover:text-[#0b5fb3] transition-colors"
                                    >
                                        <span className="block h-[2px] w-8 bg-[#2b84e8] shrink-0" />
                                        {buttonLabel}
                                    </a>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    )
}

const AccordionWidget = ({ data }) => {
    const items = data?.components || []
    const [openIndex, setOpenIndex] = useState(items.length > 0 ? 0 : null)

    if (items.length === 0) return null

    const handleToggle = (index) => {
        setOpenIndex((prev) => (prev === index ? null : index))
    }

    return (
        <section className="w-full py-8">
            <div className="mx-auto max-w-[1280px] px-4 lg:px-8">
                <div className="space-y-3">
                    {items.map((item, index) => (
                        <AccordionItem
                            key={index}
                            item={item}
                            isOpen={openIndex === index}
                            onToggle={() => handleToggle(index)}
                        />
                    ))}
                </div>
            </div>
        </section>
    )
}

export default AccordionWidget

export const config = {
    id: "capital_azur_dynamic_fields:ca_image_text",
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

const ImageTextWidget = ({ data }) => {
    const item = data?.components?.[0]

    const imageSrc    = resolveImageSrc(item?.image?.[0]?._default)
    const imageAlt    = item?.image?.[0]?.meta?.alt || ""
    const title       = item?.title
    const description = item?.description
    const buttonLabel = item?.button_label
    const buttonUrl   = item?.button_url

    return (
        <section className="w-full bg-white py-12">
            <div className="px-6 lg:px-10">
                <div className="flex flex-col md:flex-row items-center gap-12 lg:gap-16">

                    {/* Image */}
                    <div className="w-full md:w-1/2 shrink-0">
                        {imageSrc ? (
                            <img
                                src={imageSrc}
                                alt={imageAlt}
                                className="w-full h-auto object-contain"
                            />
                        ) : (
                            <div className="aspect-[4/3] bg-blue-50 flex items-center justify-center">
                                <svg className="w-20 h-20 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        )}
                    </div>

                    {/* Text */}
                    <div className="w-full md:w-1/2">
                        {title && (
                            <div className="flex items-start gap-4 mb-6">
                                <div className="w-1 shrink-0 self-stretch bg-blue-600 rounded-full mt-1" />
                                <h2 className="text-2xl md:text-3xl font-extrabold text-gray-900 uppercase leading-tight tracking-wide">
                                    {title}
                                </h2>
                            </div>
                        )}

                        {description && (
                            <p className="text-gray-600 text-sm leading-relaxed mb-8 whitespace-pre-line">
                                {description}
                            </p>
                        )}

                        {buttonUrl && (
                            <a
                                href={buttonUrl}
                                className="inline-flex items-center px-7 py-3 rounded-md bg-[#0d1b3e] text-white text-xs font-bold uppercase tracking-widest hover:bg-[#162850] transition-colors"
                            >
                                {buttonLabel || "En savoir plus"}
                            </a>
                        )}
                    </div>
                </div>
            </div>
        </section>
    )
}

export default ImageTextWidget

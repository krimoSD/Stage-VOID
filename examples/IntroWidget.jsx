export const config = {
    id: "capital_azur_dynamic_fields:ca_intro",
}

const IntroWidget = ({ data }) => {
    const item = data?.components?.[0]

    const title       = item?.title
    const description = item?.description
    const buttonLabel = item?.button_label
    const buttonUrl   = item?.button_url || "#"

    return (
        <section className="relative w-full overflow-hidden"
            style={{ background: "linear-gradient(160deg, #0d1b3e 0%, #1a3a6e 50%, #0d1b3e 100%)" }}
        >
            <div className="max-w-3xl mx-auto px-6 text-center py-20">
                <div className="w-10 h-0.5 bg-blue-500 mx-auto mb-8" />

                {title && (
                    <h2 className="text-3xl md:text-4xl font-extrabold text-white uppercase leading-tight tracking-wide mb-6">
                        {title}
                    </h2>
                )}

                {description && (
                    <p className="text-blue-100/80 text-base leading-relaxed mb-10 max-w-xl mx-auto whitespace-pre-line">
                        {description}
                    </p>
                )}

                {buttonLabel && (
                    <a
                        href={buttonUrl}
                        className="inline-flex items-center px-8 py-3 rounded-md bg-blue-600 text-white text-sm font-bold uppercase tracking-widest hover:bg-blue-700 transition-colors"
                    >
                        {buttonLabel}
                    </a>
                )}
            </div>
        </section>
    )
}

export default IntroWidget

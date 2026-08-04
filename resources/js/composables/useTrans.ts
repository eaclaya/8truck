import { usePage } from '@inertiajs/vue3';

type Replacements = Record<string, string | number>;

export function useTrans() {
    const page = usePage<{
        locale: string;
        translations: Record<string, string>;
    }>();

    const t = (key: string, replacements: Replacements = {}): string => {
        let text = page.props.translations[key] ?? key;

        for (const [search, value] of Object.entries(replacements)) {
            text = text.replace(`:${search}`, String(value));
        }

        return text;
    };

    return { t, locale: page.props.locale };
}

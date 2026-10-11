import { router, usePage } from '@inertiajs/react';
import { Bookmark, BookmarkCheck } from 'lucide-react';
import { toUrl } from '@/lib/utils';
import { login } from '@/routes';

type Props = {
    saved: boolean;
    saveUrl: string;
    unsaveUrl: string;
};

export function SaveOpeningButton({ saved, saveUrl, unsaveUrl }: Props) {
    const { auth, talent } = usePage().props;

    if (talent.candidateRemoved || talent.isSuspended) {
        return null;
    }

    if (!auth.user) {
        return (
            <a
                href={toUrl(login())}
                className="inline-flex items-center gap-2 rounded-full border border-white/40 px-6 py-3 text-sm font-medium text-white"
            >
                <Bookmark className="size-4" />
                Se connecter pour enregistrer
            </a>
        );
    }

    return (
        <button
            type="button"
            onClick={() =>
                saved
                    ? router.delete(unsaveUrl, { preserveScroll: true })
                    : router.post(saveUrl, {}, { preserveScroll: true })
            }
            className="inline-flex items-center gap-2 rounded-full border border-white/40 px-6 py-3 text-sm font-medium text-white"
        >
            {saved ? (
                <>
                    <BookmarkCheck className="size-4" />
                    Enregistré
                </>
            ) : (
                <>
                    <Bookmark className="size-4" />
                    Enregistrer pour plus tard
                </>
            )}
        </button>
    );
}

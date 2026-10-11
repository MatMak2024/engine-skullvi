import { usePage } from '@inertiajs/react';
import { StableName } from '@/components/stable-name';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-full bg-[#b7c48a] text-[11px] font-bold tracking-tight text-[#023940]">
                SK
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <StableName className="mb-0.5 truncate leading-tight font-semibold text-current">
                    {name}
                </StableName>
            </div>
        </>
    );
}

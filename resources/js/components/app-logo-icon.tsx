import type { HTMLAttributes } from 'react';

export default function AppLogoIcon({
    className = '',
    ...props
}: HTMLAttributes<HTMLSpanElement>) {
    return (
        <span
            {...props}
            className={`inline-flex items-center justify-center font-bold leading-none tracking-tight ${className}`}
        >
            SK
        </span>
    );
}

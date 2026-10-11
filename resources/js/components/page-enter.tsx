import { usePage } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import type { ReactNode } from 'react';

export function PageEnter({ children }: { children: ReactNode }) {
    const page = usePage();
    const reduce = useReducedMotion();

    return (
        <motion.div
            key={`${page.component}:${page.url}`}
            initial={reduce ? false : { opacity: 0, y: 18 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.55, ease: [0.22, 1, 0.36, 1] }}
        >
            {children}
        </motion.div>
    );
}

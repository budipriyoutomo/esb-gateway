import { Link } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { create as importScreen } from '@/routes/imports';

type Props = {
    /** esb.invoke + the module's manage permission, checked by the page. */
    canImport: boolean;
    /** kind: a MasterDataImport value, e.g. "units". */
    imports: { kind: string; label: string }[];
};

/** Links from a master data list to its spreadsheet import screens ([ESB-032]). */
export function ImportLinks({ canImport, imports }: Props) {
    if (!canImport) {
        return null;
    }

    return (
        <>
            {imports.map(({ kind, label }) => (
                <Button key={kind} variant="outline" asChild>
                    <Link href={importScreen(kind)}>
                        <Upload className="size-4" aria-hidden />
                        {label}
                    </Link>
                </Button>
            ))}
        </>
    );
}

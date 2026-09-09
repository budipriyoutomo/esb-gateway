import { Head } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Props = {
    status: number;
    message: string;
    correlationId?: string | null;
};

const TITLES: Record<number, string> = {
    404: 'Not found in the ERP',
    422: 'The ERP declined this request',
    502: 'The ERP service is unavailable',
    504: 'The ERP service timed out',
};

export default function ErrorPage({ status, message, correlationId }: Props) {
    const title = TITLES[status] ?? 'Something went wrong';

    return (
        <>
            <Head title={title} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Alert variant="destructive">
                    <AlertTriangle className="size-4" />
                    <AlertTitle>{title}</AlertTitle>
                    <AlertDescription>
                        <p>{message}</p>
                        {correlationId && (
                            <p className="mt-2 text-xs">
                                Quote this reference when reporting the problem:{' '}
                                <code className="font-mono">
                                    {correlationId}
                                </code>
                            </p>
                        )}
                    </AlertDescription>
                </Alert>
            </div>
        </>
    );
}

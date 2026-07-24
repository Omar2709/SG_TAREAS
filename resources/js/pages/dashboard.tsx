import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-lg font-semibold">Equipos</h2>
                        <p className="mt-2 text-sm text-slate-600">
                            Organiza tus grupos de trabajo y asigna responsables.
                        </p>
                        <a href="/teams" className="mt-4 inline-flex text-sm font-semibold text-slate-900">
                            Ver equipos
                        </a>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-lg font-semibold">Proyectos</h2>
                        <p className="mt-2 text-sm text-slate-600">
                            Divide el trabajo por iniciativas y su progreso.
                        </p>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-lg font-semibold">Tareas</h2>
                        <p className="mt-2 text-sm text-slate-600">
                            Gestiona prioridades, estados y asignaciones en un solo lugar.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};

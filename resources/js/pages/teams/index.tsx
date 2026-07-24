import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

interface Team {
    id: number;
    name: string;
    description?: string | null;
    owner?: { name: string };
    projects_count?: number;
}

interface Props {
    teams: Team[];
}

export default function TeamsIndex({ teams }: Props) {
    const [form, setForm] = useState({ name: '', description: '' });

    return (
        <>
            <Head title="Teams" />
            <div className="mx-auto flex max-w-5xl flex-col gap-6 p-6">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h1 className="text-2xl font-semibold">Gestión de tareas por equipos</h1>
                    <p className="mt-2 text-sm text-slate-600">
                        Crea equipos, proyectos y tareas para organizar el trabajo de forma incremental.
                    </p>
                </div>

                <form
                    method="post"
                    action="/teams"
                    className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <label className="flex flex-col gap-2 text-sm font-medium text-slate-700">
                            Nombre del equipo
                            <input
                                name="name"
                                value={form.name}
                                onChange={(event) => setForm({ ...form, name: event.target.value })}
                                className="rounded-lg border border-slate-300 px-3 py-2"
                            />
                        </label>
                        <label className="flex flex-col gap-2 text-sm font-medium text-slate-700">
                            Descripción
                            <input
                                name="description"
                                value={form.description}
                                onChange={(event) => setForm({ ...form, description: event.target.value })}
                                className="rounded-lg border border-slate-300 px-3 py-2"
                            />
                        </label>
                    </div>
                    <button className="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                        Crear equipo
                    </button>
                </form>

                <div className="grid gap-4 md:grid-cols-2">
                    {teams.map((team) => (
                        <div key={team.id} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-semibold">{team.name}</h2>
                                    <p className="text-sm text-slate-600">{team.description ?? 'Sin descripción'}</p>
                                </div>
                                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {team.projects_count ?? 0} proyectos
                                </span>
                            </div>
                            <p className="mt-4 text-sm text-slate-500">Propietario: {team.owner?.name ?? 'Sin asignar'}</p>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}

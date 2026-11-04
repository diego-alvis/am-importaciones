<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    <span>Categorías de Productos</span>
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">Organización y clasificación del catálogo general</p>
            </div>

            <button type="button" @click="$dispatch('open-create-category-modal')" onclick="window.dispatchEvent(new CustomEvent('open-create-category-modal'))" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Nueva Categoría</span>
            </button>
        </div>
    </x-slot>

    <div class="py-8" x-data="categoryManager()" @open-create-category-modal.window="showCreate = true">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Listado de Categorías -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <span class="font-bold text-sm text-slate-700">Total de Categorías: {{ $categories->count() }}</span>
                    <span class="text-xs text-slate-400">Las categorías permiten organizar los filtros en el POS y el inventario</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-400 text-xs uppercase border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-6 font-bold">#</th>
                                <th class="py-3 px-6 font-bold">Nombre de la Categoría</th>
                                <th class="py-3 px-6 font-bold text-center">Productos Vinculados</th>
                                <th class="py-3 px-6 font-bold text-center">Fecha Registro</th>
                                <th class="py-3 px-6 font-bold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($categories as $category)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6 text-xs text-slate-400 font-mono">
                                    {{ $category->id }}
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-bold text-slate-800 text-base">{{ $category->name }}</p>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-black {{ $category->products_count > 0 ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $category->products_count }} producto(s)
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center text-xs text-slate-500">
                                    {{ $category->created_at ? $category->created_at->format('d/m/Y') : '--' }}
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Editar Categoría -->
                                        <button @click="openEditModal({{ json_encode($category) }})" class="p-2 text-slate-600 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition-colors" title="Editar Categoría">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>

                                        <!-- Eliminar Categoría -->
                                        <form action="{{ route('categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar la categoría {{ $category->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors {{ $category->products_count > 0 ? 'opacity-40 cursor-not-allowed' : '' }}" {{ $category->products_count > 0 ? 'disabled' : '' }} title="{{ $category->products_count > 0 ? 'No se puede eliminar porque contiene productos' : 'Eliminar Categoría' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400 font-medium">
                                    No hay categorías registradas en el sistema.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- MODAL: CREAR CATEGORÍA -->
        <div x-show="showCreate" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.away="showCreate = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-slate-800">Nueva Categoría</h3>
                    <button @click="showCreate = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('categories.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre de la Categoría</label>
                        <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500" placeholder="Ej. Accesorios para Autos">
                    </div>

                    <div class="pt-4 flex justify-end gap-2">
                        <button type="button" @click="showCreate = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm">Guardar Categoría</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: EDITAR CATEGORÍA -->
        <div x-show="showEdit" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.away="showEdit = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-slate-800">Modificar Categoría</h3>
                    <button @click="showEdit = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>

                <form :action="'/categorias/' + editCategory.id" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre de la Categoría</label>
                        <input type="text" name="name" x-model="editCategory.name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="pt-4 flex justify-end gap-2">
                        <button type="button" @click="showEdit = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
    function categoryManager() {
        return {
            showCreate: false,
            showEdit: false,
            editCategory: {},

            init() {
                window.addEventListener('open-create-category-modal', () => {
                    this.showCreate = true;
                });
            },

            openEditModal(category) {
                this.editCategory = { ...category };
                this.showEdit = true;
            }
        }
    }
    </script>
</x-app-layout>

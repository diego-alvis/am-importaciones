<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span>Transferencias entre Sedes</span>
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">Control de movimientos de stock entre Almacén Central y Stands</p>
            </div>

            <button @click="$dispatch('open-create-transfer-modal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Nueva Transferencia</span>
            </button>
        </div>
    </x-slot>

    <div class="py-8" x-data="transferManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <span class="font-bold text-sm text-slate-700">Historial de Traslados de Mercadería</span>
                    <span class="text-xs text-slate-400">Total registrados: {{ $transfers->total() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-400 text-xs uppercase border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-6 font-bold"># Transf</th>
                                <th class="py-3 px-6 font-bold">Fecha</th>
                                <th class="py-3 px-6 font-bold">Origen</th>
                                <th class="py-3 px-6 font-bold">Destino</th>
                                <th class="py-3 px-6 font-bold">Artículos y Cantidades</th>
                                <th class="py-3 px-6 font-bold text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($transfers as $transf)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6 font-mono font-bold text-xs text-blue-600">
                                    #TR-{{ str_pad($transf->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500">
                                    {{ $transf->created_at->format('d/m/Y h:i A') }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-slate-800 text-xs block">{{ $transf->fromLocation->name }}</span>
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold">{{ $transf->fromLocation->type }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-blue-700 text-xs block">{{ $transf->toLocation->name }}</span>
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold">{{ $transf->toLocation->type }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-1">
                                        @foreach($transf->items as $item)
                                            <span class="text-xs font-semibold text-slate-700">
                                                &bull; {{ $item->product->name ?? 'Producto' }}: <strong class="text-blue-600">{{ $item->quantity }} und</strong>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $transf->status === 'recibido' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $transf->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-medium">
                                    No hay transferencias de stock registradas.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transfers->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $transfers->links() }}
                </div>
                @endif
            </div>

        </div>

        <!-- MODAL: NUEVA TRANSFERENCIA -->
        <div x-show="showCreate" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden" @click.away="showCreate = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-slate-800">Transferir Stock Entre Sedes</h3>
                    <button @click="showCreate = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('transfers.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Sede de Origen</label>
                            <select name="from_location_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Sede de Destino</label>
                            <select name="to_location_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ $loop->iteration == 2 ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-slate-700 uppercase">Productos a Trasladar</label>
                            <button type="button" @click="addItem()" class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                                + Agregar otro producto
                            </button>
                        </div>

                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-xl border border-slate-200">
                                    <div class="flex-1">
                                        <select :name="'items[' + index + '][product_id]'" required class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-medium bg-white">
                                            @foreach($products as $prod)
                                                <option value="{{ $prod->id }}">{{ $prod->sku }} - {{ $prod->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-24">
                                        <input type="number" :name="'items[' + index + '][quantity]'" min="1" value="1" required class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-black text-center" placeholder="Cant">
                                    </div>
                                    <template x-if="items.length > 1">
                                        <button type="button" @click="removeItem(index)" class="text-red-500 hover:bg-red-50 p-1.5 rounded-lg">✕</button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="showCreate = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm">Confirmar Traslado</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
    function transferManager() {
        return {
            showCreate: false,
            items: [{ product_id: '', quantity: 1 }],

            init() {
                window.addEventListener('open-create-transfer-modal', () => {
                    this.showCreate = true;
                });
            },

            addItem() {
                this.items.push({ product_id: '', quantity: 1 });
            },

            removeItem(index) {
                this.items.splice(index, 1);
            }
        }
    }
    </script>
</x-app-layout>

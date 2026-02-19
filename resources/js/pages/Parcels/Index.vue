<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Loader2, Eye, CheckCircle2, Clock, X, Upload } from 'lucide-vue-next'
import { ref, computed } from 'vue'

const props = defineProps({
  parcels: Array
})

const breadcrumbs = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Parcels' }
]

// ── Dialog state ──────────────────────────────────────────────
const createDialogOpen = ref(false)
const editDialogOpen = ref(false)
const viewDialogOpen = ref(false)
const editingParcel = ref(null)
const viewingParcel = ref(null)
const imagePreview = ref(null)
const editImagePreview = ref(null)

// ── Inline status update confirmation ─────────────────────────
const statusConfirmOpen = ref(false)
const pendingStatusChange = ref(null) // { parcel, newStatus }
const statusUpdating = ref(false)

// ── Bulk status update ─────────────────────────────────────────
const selectedIds = ref([])
const bulkStatus = ref('')
const bulkConfirmOpen = ref(false)
const bulkUpdating = ref(false)

// Only paid parcels are selectable for bulk update
const paidParcels = computed(() => props.parcels.filter(p => isPaid(p)))

const allPaidSelected = computed(() =>
    paidParcels.value.length > 0 && paidParcels.value.every(p => selectedIds.value.includes(p.id))
)

const selectedParcels = computed(() =>
    props.parcels.filter(p => selectedIds.value.includes(p.id))
)

function toggleSelectAll() {
  if (allPaidSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = paidParcels.value.map(p => p.id)
  }
}

function toggleSelect(id) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter(i => i !== id)
  } else {
    selectedIds.value = [...selectedIds.value, id]
  }
}

// ── Inline status change flow ──────────────────────────────────
function requestStatusChange(parcel, newStatus) {
  if (parcel.status === newStatus) return
  pendingStatusChange.value = { parcel, newStatus }
  statusConfirmOpen.value = true
}

function confirmStatusChange() {
  const { parcel, newStatus } = pendingStatusChange.value
  statusUpdating.value = true
  router.patch(`/parcels/${parcel.id}/status`, { status: newStatus }, {
    preserveScroll: true,
    onFinish: () => {
      statusUpdating.value = false
      statusConfirmOpen.value = false
      pendingStatusChange.value = null
    }
  })
}

// ── Bulk status update flow ────────────────────────────────────
function openBulkConfirm() {
  if (!bulkStatus.value || selectedIds.value.length === 0) return
  bulkConfirmOpen.value = true
}

function confirmBulkUpdate() {
  bulkUpdating.value = true
  router.patch('/parcels/bulk-status', {
    ids: selectedIds.value,
    status: bulkStatus.value
  }, {
    preserveScroll: true,
    onFinish: () => {
      bulkUpdating.value = false
      bulkConfirmOpen.value = false
      selectedIds.value = []
      bulkStatus.value = ''
    }
  })
}

// ── Forms ──────────────────────────────────────────────────────
const form = useForm({
  sender_first_name: '',
  sender_last_name: '',
  sender_phone: '',
  sender_national_id: '',
  origin_town: '',
  recipient_first_name: '',
  recipient_last_name: '',
  recipient_phone: '',
  recipient_national_id: '',
  destination_town: '',
  destination_address: '',
  description: '',
  image: null,
  amount: '',
  payment_phone: ''
})

const editForm = useForm({
  sender_first_name: '',
  sender_last_name: '',
  sender_phone: '',
  sender_national_id: '',
  origin_town: '',
  recipient_first_name: '',
  recipient_last_name: '',
  recipient_phone: '',
  recipient_national_id: '',
  destination_town: '',
  destination_address: '',
  description: '',
  image: null,
  remove_image: false,
  amount: '',
  payment_phone: '',
  status: ''
})

// ── Image handlers ─────────────────────────────────────────────
function handleImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (e) => { imagePreview.value = e.target.result }
    reader.readAsDataURL(file)
  }
}

function removeImage() {
  form.image = null
  imagePreview.value = null
  const input = document.getElementById('create-image-input')
  if (input) input.value = ''
}

function handleEditImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    editForm.image = file
    editForm.remove_image = false
    const reader = new FileReader()
    reader.onload = (e) => { editImagePreview.value = e.target.result }
    reader.readAsDataURL(file)
  }
}

function removeEditImage() {
  editForm.image = null
  editForm.remove_image = true
  editImagePreview.value = null
  const input = document.getElementById('edit-image-input')
  if (input) input.value = ''
}

// ── Submit / CRUD ──────────────────────────────────────────────
function submit() {
  form.post('/parcels', {
    forceFormData: true,
    onSuccess: () => {
      form.reset()
      imagePreview.value = null
      createDialogOpen.value = false
    },
    preserveScroll: true
  })
}

function viewParcel(parcel) {
  viewingParcel.value = parcel
  viewDialogOpen.value = true
}

function editParcel(parcel) {
  editingParcel.value = parcel
  editForm.sender_first_name = parcel.sender.first_name
  editForm.sender_last_name = parcel.sender.last_name
  editForm.sender_phone = parcel.sender.phone
  editForm.sender_national_id = parcel.sender.national_id_no
  editForm.origin_town = parcel.origin_town
  editForm.recipient_first_name = parcel.recipient.first_name
  editForm.recipient_last_name = parcel.recipient.last_name
  editForm.recipient_phone = parcel.recipient.phone
  editForm.recipient_national_id = parcel.recipient.national_id_no
  editForm.destination_town = parcel.destination_town
  editForm.destination_address = parcel.destination_address
  editForm.description = parcel.description || ''
  editForm.amount = parcel.amount
  editForm.payment_phone = parcel.payment_phone
  editForm.status = parcel.status
  editForm.remove_image = false
  editImagePreview.value = parcel.image_path ? `/${parcel.image_path}` : null
  editDialogOpen.value = true
}

function updateParcel() {
  editForm.transform((data) => ({ ...data, _method: 'PUT' }))
      .post(`/parcels/${editingParcel.value.id}`, {
        forceFormData: true,
        onSuccess: () => {
          editDialogOpen.value = false
          editForm.reset()
          editImagePreview.value = null
          editingParcel.value = null
        },
        preserveScroll: true
      })
}

function deleteParcel(parcelId) {
  if (confirm('Are you sure you want to delete this parcel?')) {
    router.delete(`/parcels/${parcelId}`)
  }
}

// ── Helpers ────────────────────────────────────────────────────
const STATUS_OPTIONS = [
  { value: 'pending_payment', label: 'Pending Payment' },
  { value: 'received',        label: 'Received' },
  { value: 'in_transit',      label: 'In Transit' },
  { value: 'delivered',       label: 'Delivered' },
]

function getStatusColor(status) {
  const colors = {
    pending_payment: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    received:        'bg-blue-100 text-blue-800 border-blue-200',
    in_transit:      'bg-purple-100 text-purple-800 border-purple-200',
    delivered:       'bg-green-100 text-green-800 border-green-200',
  }
  return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

function getRowClass(parcel) {
  return isPaid(parcel) ? 'bg-green-50 hover:bg-green-100' : ''
}

function isPaid(parcel) {
  return parcel.mpesa_transactions && parcel.mpesa_transactions.length > 0
}

function getPaymentTransaction(parcel) {
  return isPaid(parcel) ? parcel.mpesa_transactions[0] : null
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

function statusLabel(value) {
  return STATUS_OPTIONS.find(o => o.value === value)?.label ?? value
}
</script>

<template>
  <Head title="Parcels" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-6">

      <!-- ── Parcels List ───────────────────────────────────────── -->
      <Card class="shadow-md">
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle>All Parcels</CardTitle>
          <Button @click="createDialogOpen = true" :disabled="form.processing">
            Add Parcel
          </Button>
        </CardHeader>

        <!-- Bulk action bar — inline between header and table -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
          <div
              v-if="selectedIds.length >= 2"
              class="mx-6 mb-3 flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-5 py-3 shadow-sm"
          >
            <span class="text-sm font-semibold text-gray-800">{{ selectedIds.length }} selected</span>
            <div class="h-4 w-px bg-gray-300" />
            <span class="text-xs text-gray-500">Set status:</span>
            <Select v-model="bulkStatus">
              <SelectTrigger class="h-8 w-44 text-sm">
                <SelectValue placeholder="Choose status…" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button size="sm" :disabled="!bulkStatus" @click="openBulkConfirm">Apply</Button>
            <div class="h-4 w-px bg-gray-300" />
            <Button size="sm" variant="ghost" class="ml-auto text-gray-400 hover:text-gray-600 px-2" @click="selectedIds = []">
              <X class="h-4 w-4" />
            </Button>
          </div>
        </Transition>

        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <!-- Select-all checkbox (only for paid parcels) -->
                <TableHead class="w-10">
                  <Checkbox
                      :checked="allPaidSelected"
                      @update:checked="toggleSelectAll"
                      :disabled="paidParcels.length === 0"
                  />
                </TableHead>
                <TableHead>Tracking No.</TableHead>
                <TableHead>Sender</TableHead>
                <TableHead>Recipient</TableHead>
                <TableHead>Route</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Payment</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="parcel in parcels" :key="parcel.id" :class="getRowClass(parcel)">

                <!-- Row checkbox (only paid parcels) -->
                <TableCell>
                  <Checkbox
                      v-if="isPaid(parcel)"
                      :checked="selectedIds.includes(parcel.id)"
                      @update:checked="toggleSelect(parcel.id)"
                  />
                </TableCell>

                <TableCell class="font-mono text-sm">{{ parcel.tracking_number }}</TableCell>
                <TableCell class="text-sm">{{ parcel.sender.full_name }}</TableCell>
                <TableCell class="text-sm">{{ parcel.recipient.full_name }}</TableCell>
                <TableCell class="text-sm">{{ parcel.origin_town }} → {{ parcel.destination_town }}</TableCell>
                <TableCell class="text-sm">KES {{ parcel.amount }}</TableCell>

                <TableCell class="text-sm">
                  <div v-if="isPaid(parcel)" class="flex items-center gap-1 text-green-600">
                    <CheckCircle2 class="h-4 w-4" />
                    <span class="font-medium">{{ getPaymentTransaction(parcel).mpesa_receipt_number }}</span>
                  </div>
                  <div v-else class="flex items-center gap-1 text-yellow-600">
                    <Clock class="h-4 w-4" />
                    <span>Pending</span>
                  </div>
                </TableCell>

                <!-- Inline status select (Excel-style) -->
                <TableCell class="text-sm">
                  <Select
                      :model-value="parcel.status"
                      @update:model-value="(val) => requestStatusChange(parcel, val)"
                  >
                    <SelectTrigger
                        :class="['h-7 text-xs border font-medium rounded-full px-2', getStatusColor(parcel.status)]"
                    >
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </TableCell>

                <TableCell>
                  <div class="flex gap-2">
                    <Button @click="viewParcel(parcel)" size="sm" variant="ghost">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button @click="editParcel(parcel)" size="sm" variant="outline" :disabled="editForm.processing">
                      Edit
                    </Button>
                    <Button @click="deleteParcel(parcel.id)" size="sm" variant="destructive">
                      Delete
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- ── Inline Status Confirm Dialog ──────────────────────── -->
      <Dialog v-model:open="statusConfirmOpen" :modal="true">
        <DialogContent class="sm:max-w-[420px]">
          <DialogHeader>
            <DialogTitle>Update Status</DialogTitle>
            <DialogDescription v-if="pendingStatusChange" class="pt-2 space-y-1">
              <p class="text-sm">
                Change
                <span class="font-mono font-semibold text-orange-600">
                  {{ pendingStatusChange.parcel.tracking_number }}
                </span>
                from
                <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border', getStatusColor(pendingStatusChange.parcel.status)]">
                  {{ statusLabel(pendingStatusChange.parcel.status) }}
                </span>
                to
                <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border', getStatusColor(pendingStatusChange.newStatus)]">
                  {{ statusLabel(pendingStatusChange.newStatus) }}
                </span>
                ?
              </p>
              <p class="text-xs text-gray-500 pt-1">
                {{ pendingStatusChange.parcel.origin_town }} → {{ pendingStatusChange.parcel.destination_town }}
                &bull; {{ pendingStatusChange.parcel.recipient.full_name }}
              </p>
            </DialogDescription>
          </DialogHeader>
          <DialogFooter class="gap-2 pt-2">
            <Button variant="outline" @click="statusConfirmOpen = false" :disabled="statusUpdating">
              Cancel
            </Button>
            <Button @click="confirmStatusChange" :disabled="statusUpdating">
              <Loader2 v-if="statusUpdating" class="mr-2 h-4 w-4 animate-spin" />
              {{ statusUpdating ? 'Updating…' : 'Confirm' }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- ── Bulk Status Confirm Dialog ─────────────────────────── -->
      <Dialog v-model:open="bulkConfirmOpen" :modal="true">
        <DialogContent class="sm:max-w-[520px]">
          <DialogHeader>
            <DialogTitle>Bulk Status Update</DialogTitle>
            <DialogDescription class="pt-1">
              You are about to set
              <span class="font-semibold text-gray-900">{{ selectedParcels.length }} parcel(s)</span>
              to
              <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border', getStatusColor(bulkStatus)]">
                {{ statusLabel(bulkStatus) }}
              </span>
            </DialogDescription>
          </DialogHeader>

          <!-- Summary table -->
          <div class="max-h-60 overflow-y-auto border rounded-lg mt-2">
            <table class="w-full text-xs">
              <thead class="bg-gray-50 sticky top-0">
              <tr>
                <th class="text-left px-3 py-2 font-medium text-gray-600">Tracking</th>
                <th class="text-left px-3 py-2 font-medium text-gray-600">Route</th>
                <th class="text-left px-3 py-2 font-medium text-gray-600">Current</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="p in selectedParcels" :key="p.id" class="border-t">
                <td class="px-3 py-1.5 font-mono text-orange-600">{{ p.tracking_number }}</td>
                <td class="px-3 py-1.5 text-gray-700">{{ p.origin_town }} → {{ p.destination_town }}</td>
                <td class="px-3 py-1.5">
                    <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border', getStatusColor(p.status)]">
                      {{ statusLabel(p.status) }}
                    </span>
                </td>
              </tr>
              </tbody>
            </table>
          </div>

          <DialogFooter class="gap-2 pt-2">
            <Button variant="outline" @click="bulkConfirmOpen = false" :disabled="bulkUpdating">
              Cancel
            </Button>
            <Button @click="confirmBulkUpdate" :disabled="bulkUpdating">
              <Loader2 v-if="bulkUpdating" class="mr-2 h-4 w-4 animate-spin" />
              {{ bulkUpdating ? 'Updating…' : `Update ${selectedParcels.length} Parcel(s)` }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- ── View Parcel Dialog ─────────────────────────────────── -->
      <Dialog v-model:open="viewDialogOpen" :modal="true">
        <DialogContent class="sm:max-w-[600px]">
          <DialogHeader class="border-b pb-4">
            <div class="flex items-center justify-between">
              <DialogTitle class="text-lg font-semibold">Parcel Details</DialogTitle>
              <span v-if="viewingParcel" :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border', getStatusColor(viewingParcel.status)]">
                {{ viewingParcel.status.replace('_', ' ').toUpperCase() }}
              </span>
            </div>
          </DialogHeader>

          <div v-if="viewingParcel" class="space-y-5 py-4">
            <div>
              <p class="text-xs text-gray-500 mb-1">Tracking Number</p>
              <p class="text-base font-mono font-semibold text-orange-600">{{ viewingParcel.tracking_number }}</p>
            </div>

            <div v-if="viewingParcel.description || viewingParcel.image_path" class="flex gap-4 pt-3 border-t">
              <div v-if="viewingParcel.image_path" class="shrink-0">
                <img :src="`/${viewingParcel.image_path}`" alt="Parcel" class="rounded-md h-28 w-auto border object-cover" />
              </div>
              <div v-if="viewingParcel.description" class="flex flex-col justify-center">
                <p class="text-xs text-gray-500 mb-1">Description</p>
                <p class="text-sm text-gray-700">{{ viewingParcel.description }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-3 border-t">
              <div>
                <p class="text-xs font-semibold text-gray-700 mb-2">Sender</p>
                <div class="space-y-1.5 text-sm">
                  <div><p class="text-xs text-gray-500">Name</p><p class="font-medium">{{ viewingParcel.sender.full_name }}</p></div>
                  <div><p class="text-xs text-gray-500">Phone</p><p class="font-mono">{{ viewingParcel.sender.phone }}</p></div>
                  <div><p class="text-xs text-gray-500">Origin</p><p class="font-medium">{{ viewingParcel.origin_town }}</p></div>
                </div>
              </div>
              <div>
                <p class="text-xs font-semibold text-gray-700 mb-2">Recipient</p>
                <div class="space-y-1.5 text-sm">
                  <div><p class="text-xs text-gray-500">Name</p><p class="font-medium">{{ viewingParcel.recipient.full_name }}</p></div>
                  <div><p class="text-xs text-gray-500">Phone</p><p class="font-mono">{{ viewingParcel.recipient.phone }}</p></div>
                  <div><p class="text-xs text-gray-500">Destination</p><p class="font-medium">{{ viewingParcel.destination_town }}</p></div>
                </div>
              </div>
            </div>

            <div class="pt-3 border-t">
              <p class="text-xs text-gray-500 mb-1">Delivery Address</p>
              <p class="text-sm text-gray-700">{{ viewingParcel.destination_address }}</p>
            </div>

            <div class="pt-3 border-t">
              <div class="flex items-baseline justify-between mb-2">
                <p class="text-xs font-semibold text-gray-700">Payment</p>
                <p class="text-lg font-bold text-gray-900">KES {{ parseFloat(viewingParcel.amount).toLocaleString() }}</p>
              </div>
              <div class="space-y-1.5 text-sm">
                <div><p class="text-xs text-gray-500">M-Pesa Number</p><p class="font-mono">{{ viewingParcel.payment_phone }}</p></div>
                <div v-if="isPaid(viewingParcel)" class="flex items-center gap-1.5 text-green-600 pt-1">
                  <CheckCircle2 class="h-4 w-4" />
                  <span class="text-xs font-semibold">PAID - {{ getPaymentTransaction(viewingParcel).mpesa_receipt_number }}</span>
                </div>
                <div v-else class="flex items-center gap-1.5 text-yellow-600 pt-1">
                  <Clock class="h-4 w-4" />
                  <span class="text-xs font-semibold">PENDING PAYMENT</span>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t">
              <p class="text-xs text-gray-400">{{ formatDate(viewingParcel.created_at) }}</p>
              <div class="flex gap-2">
                <Button type="button" variant="outline" size="sm" @click="viewDialogOpen = false">Close</Button>
                <Button type="button" size="sm" @click="() => { viewDialogOpen = false; editParcel(viewingParcel); }">Edit</Button>
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- ── Create Parcel Dialog ───────────────────────────────── -->
      <Dialog v-model:open="createDialogOpen" :modal="true">
        <DialogContent class="sm:max-w-[900px] max-h-[90vh] overflow-y-auto" @interact-outside="(e) => e.preventDefault()">
          <DialogHeader>
            <DialogTitle class="text-xl">Add New Parcel</DialogTitle>
          </DialogHeader>

          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <div class="space-y-4 border rounded-lg p-4">
                <h3 class="font-semibold text-base">Sender Details</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5"><Label class="text-sm">First Name</Label><Input v-model="form.sender_first_name" :disabled="form.processing" required /></div>
                  <div class="space-y-1.5"><Label class="text-sm">Last Name</Label><Input v-model="form.sender_last_name" :disabled="form.processing" required /></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5"><Label class="text-sm">Phone</Label><Input v-model="form.sender_phone" :disabled="form.processing" required /></div>
                  <div class="space-y-1.5"><Label class="text-sm">National ID</Label><Input v-model="form.sender_national_id" :disabled="form.processing" required /></div>
                </div>
                <div class="space-y-1.5"><Label class="text-sm">Origin Town</Label><Input v-model="form.origin_town" :disabled="form.processing" required /></div>
              </div>

              <div class="space-y-4 border rounded-lg p-4">
                <h3 class="font-semibold text-base">Recipient Details</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5"><Label class="text-sm">First Name</Label><Input v-model="form.recipient_first_name" :disabled="form.processing" required /></div>
                  <div class="space-y-1.5"><Label class="text-sm">Last Name</Label><Input v-model="form.recipient_last_name" :disabled="form.processing" required /></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5"><Label class="text-sm">Phone</Label><Input v-model="form.recipient_phone" :disabled="form.processing" required /></div>
                  <div class="space-y-1.5"><Label class="text-sm">National ID</Label><Input v-model="form.recipient_national_id" :disabled="form.processing" required /></div>
                </div>
                <div class="space-y-1.5"><Label class="text-sm">Destination Town</Label><Input v-model="form.destination_town" :disabled="form.processing" required /></div>
                <div class="space-y-1.5"><Label class="text-sm">Destination Address</Label><Textarea v-model="form.destination_address" :disabled="form.processing" rows="3" required /></div>
              </div>
            </div>

            <div class="space-y-4 border rounded-lg p-4">
              <h3 class="font-semibold text-base">Parcel Details</h3>
              <div class="space-y-4">
                <div class="space-y-1.5">
                  <Label class="text-sm">Description (Optional)</Label>
                  <Textarea v-model="form.description" :disabled="form.processing" rows="3" placeholder="Describe the parcel contents..." />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Parcel Image (Optional)</Label>
                  <div class="space-y-2">
                    <div v-if="imagePreview" class="relative inline-block">
                      <img :src="imagePreview" alt="Preview" class="h-32 w-auto rounded-lg border" />
                      <Button type="button" size="sm" variant="destructive" class="absolute -top-2 -right-2" @click="removeImage" :disabled="form.processing">
                        <X class="h-3 w-3" />
                      </Button>
                    </div>
                    <div v-else class="border-2 border-dashed rounded-lg p-4">
                      <label for="create-image-input" class="cursor-pointer flex flex-col items-center gap-2">
                        <Upload class="h-8 w-8 text-gray-400" />
                        <span class="text-sm text-gray-600">Click to upload image</span>
                        <span class="text-xs text-gray-400">JPG, PNG, WEBP (Max 2MB)</span>
                      </label>
                      <input id="create-image-input" type="file" accept="image/*" class="hidden" @change="handleImageChange" :disabled="form.processing" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-4 border rounded-lg p-4">
              <h3 class="font-semibold text-base">Payment Details</h3>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5"><Label class="text-sm">Amount (KES)</Label><Input v-model="form.amount" type="number" step="0.01" :disabled="form.processing" required /></div>
                <div class="space-y-1.5"><Label class="text-sm">M-Pesa Phone Number</Label><Input v-model="form.payment_phone" placeholder="0712345678" :disabled="form.processing" required /></div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <Button type="button" variant="outline" @click="createDialogOpen = false" :disabled="form.processing">Cancel</Button>
              <Button type="submit" size="lg" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ form.processing ? 'Saving...' : 'Save Parcel' }}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <!-- ── Edit Parcel Dialog ─────────────────────────────────── -->
      <Dialog v-model:open="editDialogOpen" :modal="true">
        <DialogContent class="sm:max-w-[900px] max-h-[90vh] overflow-y-auto" @interact-outside="(e) => e.preventDefault()">
          <DialogHeader>
            <DialogTitle class="text-xl">Edit Parcel</DialogTitle>
          </DialogHeader>

          <form @submit.prevent="updateParcel" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <div class="space-y-4 border rounded-lg p-4">
                <h3 class="font-semibold text-base">Sender Details</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5"><Label class="text-sm">First Name</Label><Input v-model="editForm.sender_first_name" :disabled="editForm.processing" required /></div>
                  <div class="space-y-1.5"><Label class="text-sm">Last Name</Label><Input v-model="editForm.sender_last_name" :disabled="editForm.processing" required /></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5"><Label class="text-sm">Phone</Label><Input v-model="editForm.sender_phone" :disabled="editForm.processing" required /></div>
                  <div class="space-y-1.5"><Label class="text-sm">National ID</Label><Input v-model="editForm.sender_national_id" :disabled="editForm.processing" required /></div>
                </div>
                <div class="space-y-1.5"><Label class="text-sm">Origin Town</Label><Input v-model="editForm.origin_town" :disabled="editForm.processing" required /></div>
              </div>

              <div class="space-y-4 border rounded-lg p-4">
                <h3 class="font-semibold text-base">Recipient Details</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5"><Label class="text-sm">First Name</Label><Input v-model="editForm.recipient_first_name" :disabled="editForm.processing" required /></div>
                  <div class="space-y-1.5"><Label class="text-sm">Last Name</Label><Input v-model="editForm.recipient_last_name" :disabled="editForm.processing" required /></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5"><Label class="text-sm">Phone</Label><Input v-model="editForm.recipient_phone" :disabled="editForm.processing" required /></div>
                  <div class="space-y-1.5"><Label class="text-sm">National ID</Label><Input v-model="editForm.recipient_national_id" :disabled="editForm.processing" required /></div>
                </div>
                <div class="space-y-1.5"><Label class="text-sm">Destination Town</Label><Input v-model="editForm.destination_town" :disabled="editForm.processing" required /></div>
                <div class="space-y-1.5"><Label class="text-sm">Destination Address</Label><Textarea v-model="editForm.destination_address" :disabled="editForm.processing" rows="3" required /></div>
              </div>
            </div>

            <div class="space-y-4 border rounded-lg p-4">
              <h3 class="font-semibold text-base">Parcel Details</h3>
              <div class="space-y-4">
                <div class="space-y-1.5">
                  <Label class="text-sm">Description (Optional)</Label>
                  <Textarea v-model="editForm.description" :disabled="editForm.processing" rows="3" placeholder="Describe the parcel contents..." />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Parcel Image (Optional)</Label>
                  <div class="space-y-2">
                    <div v-if="editImagePreview && !editForm.remove_image" class="relative inline-block">
                      <img :src="editImagePreview" alt="Preview" class="h-32 w-auto rounded-lg border" />
                      <Button type="button" size="sm" variant="destructive" class="absolute -top-2 -right-2" @click="removeEditImage" :disabled="editForm.processing">
                        <X class="h-3 w-3" />
                      </Button>
                    </div>
                    <div v-else class="border-2 border-dashed rounded-lg p-4">
                      <label for="edit-image-input" class="cursor-pointer flex flex-col items-center gap-2">
                        <Upload class="h-8 w-8 text-gray-400" />
                        <span class="text-sm text-gray-600">Click to upload image</span>
                        <span class="text-xs text-gray-400">JPG, PNG, WEBP (Max 2MB)</span>
                      </label>
                      <input id="edit-image-input" type="file" accept="image/*" class="hidden" @change="handleEditImageChange" :disabled="editForm.processing" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-4 border rounded-lg p-4">
              <h3 class="font-semibold text-base">Payment Details</h3>
              <div class="grid grid-cols-3 gap-4">
                <div class="space-y-1.5"><Label class="text-sm">Amount (KES)</Label><Input v-model="editForm.amount" type="number" step="0.01" :disabled="editForm.processing" required /></div>
                <div class="space-y-1.5"><Label class="text-sm">M-Pesa Phone Number</Label><Input v-model="editForm.payment_phone" placeholder="0712345678" :disabled="editForm.processing" required /></div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Status</Label>
                  <Select v-model="editForm.status" :disabled="editForm.processing">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <Button type="button" variant="outline" @click="editDialogOpen = false" :disabled="editForm.processing">Cancel</Button>
              <Button type="submit" size="lg" :disabled="editForm.processing">
                <Loader2 v-if="editForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ editForm.processing ? 'Updating...' : 'Update Parcel' }}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

    </div>

  </AppLayout>
</template>
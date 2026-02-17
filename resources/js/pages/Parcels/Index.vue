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
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Loader2, Eye, User, MapPin, Banknote, Package, CheckCircle2, Clock, X, Upload, ImageIcon } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps({
  parcels: Array
})

const breadcrumbs = [
  {
    title: 'Dashboard',
    href: dashboard().url,
  },
  {
    title: 'Parcels',
  }
]

const createDialogOpen = ref(false)
const editDialogOpen = ref(false)
const viewDialogOpen = ref(false)
const editingParcel = ref(null)
const viewingParcel = ref(null)
const imagePreview = ref(null)
const editImagePreview = ref(null)

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

function handleImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeImage() {
  form.image = null
  imagePreview.value = null
  const fileInput = document.getElementById('create-image-input')
  if (fileInput) fileInput.value = ''
}

function handleEditImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    editForm.image = file
    editForm.remove_image = false
    const reader = new FileReader()
    reader.onload = (e) => {
      editImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeEditImage() {
  editForm.image = null
  editForm.remove_image = true
  editImagePreview.value = null
  const fileInput = document.getElementById('edit-image-input')
  if (fileInput) fileInput.value = ''
}

function submit() {
  form.post('/parcels', {
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
  editForm.put(`/parcels/${editingParcel.value.id}`, {
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

function getStatusColor(status) {
  const colors = {
    'pending_payment': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'received': 'bg-green-100 text-green-800 border-green-200',
    'in_transit': 'bg-purple-100 text-purple-800 border-purple-200',
    'delivered': 'bg-green-100 text-green-800 border-green-200'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

function getRowClass(parcel) {
  if (parcel.mpesa_transactions && parcel.mpesa_transactions.length > 0) {
    return 'bg-green-50 hover:bg-green-100'
  }
  return ''
}

function isPaid(parcel) {
  return parcel.mpesa_transactions && parcel.mpesa_transactions.length > 0
}

function getPaymentTransaction(parcel) {
  return parcel.mpesa_transactions && parcel.mpesa_transactions.length > 0
      ? parcel.mpesa_transactions[0]
      : null
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <Head title="Parcels" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-6">

      <!-- Parcels List -->
      <Card class="shadow-md">
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle>All Parcels</CardTitle>
          <Button @click="createDialogOpen = true" :disabled="form.processing">
            Add Parcel
          </Button>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
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
                <TableCell class="text-sm">
                  <span :class="['inline-flex items-center rounded-full px-2 py-1 text-xs font-medium', getStatusColor(parcel.status)]">
                    {{ parcel.status.replace('_', ' ') }}
                  </span>
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

      <!-- View Parcel Dialog -->
      <Dialog v-model:open="viewDialogOpen" :modal="true">
        <DialogContent class="sm:max-w-[700px]">
          <DialogHeader class="border-b pb-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Package class="h-5 w-5 text-orange-600" />
                <DialogTitle class="text-xl font-bold">Parcel Details</DialogTitle>
              </div>
              <span v-if="viewingParcel" :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border', getStatusColor(viewingParcel.status)]">
                {{ viewingParcel.status.replace('_', ' ').toUpperCase() }}
              </span>
            </div>
          </DialogHeader>

          <div v-if="viewingParcel" class="space-y-4 py-4">
            <!-- Tracking Number -->
            <div class="bg-orange-50 rounded-lg p-3 border border-orange-200">
              <p class="text-xs text-gray-600 mb-0.5">Tracking Number</p>
              <p class="text-lg font-bold font-mono text-orange-600">{{ viewingParcel.tracking_number }}</p>
            </div>

            <!-- Description and Image -->
            <div v-if="viewingParcel.description || viewingParcel.image_path" class="space-y-3">
              <div v-if="viewingParcel.description" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-xs text-gray-600 mb-1">Description</p>
                <p class="text-sm">{{ viewingParcel.description }}</p>
              </div>
              <div v-if="viewingParcel.image_path" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-xs text-gray-600 mb-2">Parcel Image</p>
                <img :src="`/${viewingParcel.image_path}`" alt="Parcel" class="rounded-lg max-h-48 w-auto" />
              </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-2 gap-4">
              <!-- Sender -->
              <div class="space-y-2 p-3 bg-blue-50 rounded-lg border border-blue-100">
                <div class="flex items-center gap-1.5 mb-2">
                  <User class="h-4 w-4 text-blue-600" />
                  <h3 class="font-semibold text-sm text-blue-900">Sender</h3>
                </div>
                <div>
                  <p class="text-xs text-gray-600">Name</p>
                  <p class="text-sm font-semibold">{{ viewingParcel.sender.full_name }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600">Phone</p>
                  <p class="text-sm font-mono">{{ viewingParcel.sender.phone }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600">National ID</p>
                  <p class="text-sm font-mono">{{ viewingParcel.sender.national_id_no }}</p>
                </div>
                <div class="pt-1 border-t border-blue-200">
                  <p class="text-xs text-gray-600 flex items-center gap-1">
                    <MapPin class="h-3 w-3" />
                    Origin
                  </p>
                  <p class="text-sm font-semibold text-blue-700">{{ viewingParcel.origin_town }}</p>
                </div>
              </div>

              <!-- Recipient -->
              <div class="space-y-2 p-3 bg-green-50 rounded-lg border border-green-100">
                <div class="flex items-center gap-1.5 mb-2">
                  <User class="h-4 w-4 text-green-600" />
                  <h3 class="font-semibold text-sm text-green-900">Recipient</h3>
                </div>
                <div>
                  <p class="text-xs text-gray-600">Name</p>
                  <p class="text-sm font-semibold">{{ viewingParcel.recipient.full_name }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600">Phone</p>
                  <p class="text-sm font-mono">{{ viewingParcel.recipient.phone }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600">National ID</p>
                  <p class="text-sm font-mono">{{ viewingParcel.recipient.national_id_no }}</p>
                </div>
                <div class="pt-1 border-t border-green-200">
                  <p class="text-xs text-gray-600 flex items-center gap-1">
                    <MapPin class="h-3 w-3" />
                    Destination
                  </p>
                  <p class="text-sm font-semibold text-green-700">{{ viewingParcel.destination_town }}</p>
                </div>
              </div>
            </div>

            <!-- Address & Payment Row -->
            <div class="grid grid-cols-2 gap-4">
              <!-- Address -->
              <div class="p-3 bg-purple-50 rounded-lg border border-purple-100">
                <div class="flex items-center gap-1.5 mb-2">
                  <MapPin class="h-4 w-4 text-purple-600" />
                  <h3 class="font-semibold text-sm text-purple-900">Delivery Address</h3>
                </div>
                <p class="text-sm leading-relaxed">{{ viewingParcel.destination_address }}</p>
              </div>

              <!-- Payment -->
              <div :class="['p-3 rounded-lg border', isPaid(viewingParcel) ? 'bg-green-50 border-green-200' : 'bg-orange-50 border-orange-100']">
                <div class="flex items-center gap-1.5 mb-2">
                  <Banknote :class="['h-4 w-4', isPaid(viewingParcel) ? 'text-green-600' : 'text-orange-600']" />
                  <h3 :class="['font-semibold text-sm', isPaid(viewingParcel) ? 'text-green-900' : 'text-orange-900']">Payment</h3>
                </div>
                <div class="space-y-2">
                  <div>
                    <p class="text-xs text-gray-600">Amount</p>
                    <p :class="['text-lg font-bold', isPaid(viewingParcel) ? 'text-green-600' : 'text-orange-600']">
                      KES {{ parseFloat(viewingParcel.amount).toLocaleString() }}
                    </p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-600">M-Pesa Number</p>
                    <p class="text-sm font-mono">{{ viewingParcel.payment_phone }}</p>
                  </div>
                  <div v-if="isPaid(viewingParcel)" class="pt-2 border-t border-green-200">
                    <div class="flex items-center gap-1 text-green-600 mb-1">
                      <CheckCircle2 class="h-4 w-4" />
                      <p class="text-xs font-semibold">PAID</p>
                    </div>
                    <p class="text-xs text-gray-600">Receipt Number</p>
                    <p class="text-sm font-mono font-bold text-green-700">{{ getPaymentTransaction(viewingParcel).mpesa_receipt_number }}</p>
                  </div>
                  <div v-else class="pt-2 border-t border-orange-200">
                    <div class="flex items-center gap-1 text-yellow-600">
                      <Clock class="h-4 w-4" />
                      <p class="text-xs font-semibold">PENDING PAYMENT</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between pt-3 border-t">
              <div class="text-xs text-gray-500">
                Created: {{ formatDate(viewingParcel.created_at) }} • Updated: {{ formatDate(viewingParcel.updated_at) }}
              </div>
              <div class="flex gap-2">
                <Button type="button" variant="outline" size="sm" @click="viewDialogOpen = false">
                  Close
                </Button>
                <Button type="button" size="sm" @click="() => { viewDialogOpen = false; editParcel(viewingParcel); }">
                  Edit Parcel
                </Button>
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Create Parcel Dialog -->
      <Dialog v-model:open="createDialogOpen" :modal="true">
        <DialogContent class="sm:max-w-[900px] max-h-[90vh] overflow-y-auto" @interact-outside="(e) => e.preventDefault()">
          <DialogHeader>
            <DialogTitle class="text-xl">Add New Parcel</DialogTitle>
          </DialogHeader>

          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <!-- Sender Details -->
              <div class="space-y-4 border rounded-lg p-4">
                <h3 class="font-semibold text-base">Sender Details</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <Label class="text-sm">First Name</Label>
                    <Input v-model="form.sender_first_name" :disabled="form.processing" required />
                  </div>
                  <div class="space-y-1.5">
                    <Label class="text-sm">Last Name</Label>
                    <Input v-model="form.sender_last_name" :disabled="form.processing" required />
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <Label class="text-sm">Phone</Label>
                    <Input v-model="form.sender_phone" :disabled="form.processing" required />
                  </div>
                  <div class="space-y-1.5">
                    <Label class="text-sm">National ID</Label>
                    <Input v-model="form.sender_national_id" :disabled="form.processing" required />
                  </div>
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Origin Town</Label>
                  <Input v-model="form.origin_town" :disabled="form.processing" required />
                </div>
              </div>

              <!-- Recipient Details -->
              <div class="space-y-4 border rounded-lg p-4">
                <h3 class="font-semibold text-base">Recipient Details</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <Label class="text-sm">First Name</Label>
                    <Input v-model="form.recipient_first_name" :disabled="form.processing" required />
                  </div>
                  <div class="space-y-1.5">
                    <Label class="text-sm">Last Name</Label>
                    <Input v-model="form.recipient_last_name" :disabled="form.processing" required />
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <Label class="text-sm">Phone</Label>
                    <Input v-model="form.recipient_phone" :disabled="form.processing" required />
                  </div>
                  <div class="space-y-1.5">
                    <Label class="text-sm">National ID</Label>
                    <Input v-model="form.recipient_national_id" :disabled="form.processing" required />
                  </div>
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Destination Town</Label>
                  <Input v-model="form.destination_town" :disabled="form.processing" required />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Destination Address</Label>
                  <Textarea v-model="form.destination_address" :disabled="form.processing" rows="3" required />
                </div>
              </div>
            </div>

            <!-- Parcel Details -->
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
                      <Button
                          type="button"
                          size="sm"
                          variant="destructive"
                          class="absolute -top-2 -right-2"
                          @click="removeImage"
                          :disabled="form.processing"
                      >
                        <X class="h-3 w-3" />
                      </Button>
                    </div>
                    <div v-else class="border-2 border-dashed rounded-lg p-4">
                      <label for="create-image-input" class="cursor-pointer flex flex-col items-center gap-2">
                        <Upload class="h-8 w-8 text-gray-400" />
                        <span class="text-sm text-gray-600">Click to upload image</span>
                        <span class="text-xs text-gray-400">JPG, PNG, WEBP (Max 2MB)</span>
                      </label>
                      <input
                          id="create-image-input"
                          type="file"
                          accept="image/*"
                          class="hidden"
                          @change="handleImageChange"
                          :disabled="form.processing"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment Details -->
            <div class="space-y-4 border rounded-lg p-4">
              <h3 class="font-semibold text-base">Payment Details</h3>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <Label class="text-sm">Amount (KES)</Label>
                  <Input v-model="form.amount" type="number" step="0.01" :disabled="form.processing" required />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">M-Pesa Phone Number</Label>
                  <Input v-model="form.payment_phone" placeholder="0712345678" :disabled="form.processing" required />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <Button type="button" variant="outline" @click="createDialogOpen = false" :disabled="form.processing">
                Cancel
              </Button>
              <Button type="submit" size="lg" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ form.processing ? 'Saving...' : 'Save Parcel' }}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Edit Parcel Dialog -->
      <Dialog v-model:open="editDialogOpen" :modal="true">
        <DialogContent class="sm:max-w-[900px] max-h-[90vh] overflow-y-auto" @interact-outside="(e) => e.preventDefault()">
          <DialogHeader>
            <DialogTitle class="text-xl">Edit Parcel</DialogTitle>
          </DialogHeader>

          <form @submit.prevent="updateParcel" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <!-- Sender Details -->
              <div class="space-y-4 border rounded-lg p-4">
                <h3 class="font-semibold text-base">Sender Details</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <Label class="text-sm">First Name</Label>
                    <Input v-model="editForm.sender_first_name" :disabled="editForm.processing" required />
                  </div>
                  <div class="space-y-1.5">
                    <Label class="text-sm">Last Name</Label>
                    <Input v-model="editForm.sender_last_name" :disabled="editForm.processing" required />
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <Label class="text-sm">Phone</Label>
                    <Input v-model="editForm.sender_phone" :disabled="editForm.processing" required />
                  </div>
                  <div class="space-y-1.5">
                    <Label class="text-sm">National ID</Label>
                    <Input v-model="editForm.sender_national_id" :disabled="editForm.processing" required />
                  </div>
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Origin Town</Label>
                  <Input v-model="editForm.origin_town" :disabled="editForm.processing" required />
                </div>
              </div>

              <!-- Recipient Details -->
              <div class="space-y-4 border rounded-lg p-4">
                <h3 class="font-semibold text-base">Recipient Details</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <Label class="text-sm">First Name</Label>
                    <Input v-model="editForm.recipient_first_name" :disabled="editForm.processing" required />
                  </div>
                  <div class="space-y-1.5">
                    <Label class="text-sm">Last Name</Label>
                    <Input v-model="editForm.recipient_last_name" :disabled="editForm.processing" required />
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <Label class="text-sm">Phone</Label>
                    <Input v-model="editForm.recipient_phone" :disabled="editForm.processing" required />
                  </div>
                  <div class="space-y-1.5">
                    <Label class="text-sm">National ID</Label>
                    <Input v-model="editForm.recipient_national_id" :disabled="editForm.processing" required />
                  </div>
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Destination Town</Label>
                  <Input v-model="editForm.destination_town" :disabled="editForm.processing" required />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Destination Address</Label>
                  <Textarea v-model="editForm.destination_address" :disabled="editForm.processing" rows="3" required />
                </div>
              </div>
            </div>

            <!-- Parcel Details -->
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
                      <Button
                          type="button"
                          size="sm"
                          variant="destructive"
                          class="absolute -top-2 -right-2"
                          @click="removeEditImage"
                          :disabled="editForm.processing"
                      >
                        <X class="h-3 w-3" />
                      </Button>
                    </div>
                    <div v-else class="border-2 border-dashed rounded-lg p-4">
                      <label for="edit-image-input" class="cursor-pointer flex flex-col items-center gap-2">
                        <Upload class="h-8 w-8 text-gray-400" />
                        <span class="text-sm text-gray-600">Click to upload image</span>
                        <span class="text-xs text-gray-400">JPG, PNG, WEBP (Max 2MB)</span>
                      </label>
                      <input
                          id="edit-image-input"
                          type="file"
                          accept="image/*"
                          class="hidden"
                          @change="handleEditImageChange"
                          :disabled="editForm.processing"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment Details with Status -->
            <div class="space-y-4 border rounded-lg p-4">
              <h3 class="font-semibold text-base">Payment Details</h3>
              <div class="grid grid-cols-3 gap-4">
                <div class="space-y-1.5">
                  <Label class="text-sm">Amount (KES)</Label>
                  <Input v-model="editForm.amount" type="number" step="0.01" :disabled="editForm.processing" required />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">M-Pesa Phone Number</Label>
                  <Input v-model="editForm.payment_phone" placeholder="0712345678" :disabled="editForm.processing" required />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-sm">Status</Label>
                  <Select v-model="editForm.status" :disabled="editForm.processing">
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="pending_payment">Pending Payment</SelectItem>
                      <SelectItem value="received">Received</SelectItem>
                      <SelectItem value="in_transit">In Transit</SelectItem>
                      <SelectItem value="delivered">Delivered</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <Button type="button" variant="outline" @click="editDialogOpen = false" :disabled="editForm.processing">
                Cancel
              </Button>
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
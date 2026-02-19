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

// const form = useForm({
//   sender_first_name: 'John',
//   sender_last_name: 'Doe',
//   sender_phone: '0712345678',
//   sender_national_id: '12345678',
//   origin_town: 'Nairobi',
//   recipient_first_name: 'Jane',
//   recipient_last_name: 'Smith',
//   recipient_phone: '0798765432',
//   recipient_national_id: '87654321',
//   destination_town: 'Mombasa',
//   destination_address: 'Nyali Beach Road, Apartment 5B',
//   description: 'Electronics - Handle with care',
//   image: null,
//   amount: '500',
//   payment_phone: '0712419949'
// })

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
  console.log('File selected:', file)
  if (file) {
    console.log('File details:', {
      name: file.name,
      size: file.size,
      type: file.type
    })
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
  console.log('=== FORM SUBMISSION DEBUG ===')
  console.log('Form image:', form.image)
  console.log('Form image type:', typeof form.image)
  console.log('Is File?:', form.image instanceof File)

  form.post('/parcels', {
    forceFormData: true,
    onBefore: () => {
      console.log('onBefore: Request about to start')
    },
    onStart: () => {
      console.log('onStart: Request started')
    },
    onProgress: (progress) => {
      console.log('onProgress:', progress)
    },
    onSuccess: (response) => {
      console.log('onSuccess:', response)
      form.reset()
      imagePreview.value = null
      createDialogOpen.value = false
    },
    onError: (errors) => {
      console.error('onError:', errors)
    },
    onFinish: () => {
      console.log('onFinish: Request complete')
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
  editForm.transform((data) => ({
    ...data,
    _method: 'PUT'
  })).post(`/parcels/${editingParcel.value.id}`, {
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
            <!-- Tracking -->
            <div>
              <p class="text-xs text-gray-500 mb-1">Tracking Number</p>
              <p class="text-base font-mono font-semibold text-orange-600">{{ viewingParcel.tracking_number }}</p>
            </div>

            <!-- Description & Image (if present) -->
            <div v-if="viewingParcel.description || viewingParcel.image_path" class="flex gap-4 pt-3 border-t">
              <div v-if="viewingParcel.image_path" class="shrink-0">
                <img :src="`/${viewingParcel.image_path}`" alt="Parcel" class="rounded-md h-28 w-auto border object-cover" />
              </div>
              <div v-if="viewingParcel.description" class="flex flex-col justify-center">
                <p class="text-xs text-gray-500 mb-1">Description</p>
                <p class="text-sm text-gray-700">{{ viewingParcel.description }}</p>
              </div>
            </div>

            <!-- Sender & Recipient (Grid) -->
            <div class="grid grid-cols-2 gap-4 pt-3 border-t">
              <div>
                <p class="text-xs font-semibold text-gray-700 mb-2">Sender</p>
                <div class="space-y-1.5 text-sm">
                  <div>
                    <p class="text-xs text-gray-500">Name</p>
                    <p class="font-medium">{{ viewingParcel.sender.full_name }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Phone</p>
                    <p class="font-mono">{{ viewingParcel.sender.phone }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Origin</p>
                    <p class="font-medium">{{ viewingParcel.origin_town }}</p>
                  </div>
                </div>
              </div>

              <div>
                <p class="text-xs font-semibold text-gray-700 mb-2">Recipient</p>
                <div class="space-y-1.5 text-sm">
                  <div>
                    <p class="text-xs text-gray-500">Name</p>
                    <p class="font-medium">{{ viewingParcel.recipient.full_name }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Phone</p>
                    <p class="font-mono">{{ viewingParcel.recipient.phone }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Destination</p>
                    <p class="font-medium">{{ viewingParcel.destination_town }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Address -->
            <div class="pt-3 border-t">
              <p class="text-xs text-gray-500 mb-1">Delivery Address</p>
              <p class="text-sm text-gray-700">{{ viewingParcel.destination_address }}</p>
            </div>

            <!-- Payment -->
            <div class="pt-3 border-t">
              <div class="flex items-baseline justify-between mb-2">
                <p class="text-xs font-semibold text-gray-700">Payment</p>
                <p class="text-lg font-bold text-gray-900">KES {{ parseFloat(viewingParcel.amount).toLocaleString() }}</p>
              </div>
              <div class="space-y-1.5 text-sm">
                <div>
                  <p class="text-xs text-gray-500">M-Pesa Number</p>
                  <p class="font-mono">{{ viewingParcel.payment_phone }}</p>
                </div>
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

            <!-- Footer -->
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
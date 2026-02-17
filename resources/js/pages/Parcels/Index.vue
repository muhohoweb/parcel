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
import { Loader2, Eye, User, MapPin, Banknote, Package } from 'lucide-vue-next'
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
  amount: '',
  payment_phone: '',
  status: ''
})

function submit() {
  form.post('/parcels', {
    onSuccess: () => {
      form.reset()
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
  editForm.amount = parcel.amount
  editForm.payment_phone = parcel.payment_phone
  editForm.status = parcel.status
  editDialogOpen.value = true
}

function updateParcel() {
  editForm.put(`/parcels/${editingParcel.value.id}`, {
    onSuccess: () => {
      editDialogOpen.value = false
      editForm.reset()
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
    'received': 'bg-blue-100 text-blue-800 border-blue-200',
    'in_transit': 'bg-purple-100 text-purple-800 border-purple-200',
    'delivered': 'bg-green-100 text-green-800 border-green-200'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
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
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="parcel in parcels" :key="parcel.id">
                <TableCell class="font-mono text-sm">{{ parcel.tracking_number }}</TableCell>
                <TableCell class="text-sm">{{ parcel.sender.full_name }}</TableCell>
                <TableCell class="text-sm">{{ parcel.recipient.full_name }}</TableCell>
                <TableCell class="text-sm">{{ parcel.origin_town }} → {{ parcel.destination_town }}</TableCell>
                <TableCell class="text-sm">KES {{ parcel.amount }}</TableCell>
                <TableCell class="text-sm">
                  <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800">
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
        <DialogContent class="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle class="text-2xl font-bold flex items-center gap-2">
              <Package class="h-6 w-6 text-orange-600" />
              Parcel Details
            </DialogTitle>
          </DialogHeader>

          <div v-if="viewingParcel" class="space-y-6">
            <!-- Tracking & Status -->
            <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-6 border border-orange-200">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 mb-1">Tracking Number</p>
                  <p class="text-2xl font-bold font-mono text-orange-600">{{ viewingParcel.tracking_number }}</p>
                </div>
                <div>
                  <span :class="['inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold border', getStatusColor(viewingParcel.status)]">
                    {{ viewingParcel.status.replace('_', ' ').toUpperCase() }}
                  </span>
                </div>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <!-- Sender Information -->
              <Card class="border-2">
                <CardHeader class="pb-3 bg-blue-50">
                  <CardTitle class="text-lg flex items-center gap-2">
                    <User class="h-5 w-5 text-blue-600" />
                    Sender Information
                  </CardTitle>
                </CardHeader>
                <CardContent class="pt-4 space-y-3">
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Full Name</p>
                    <p class="text-base font-semibold">{{ viewingParcel.sender.full_name }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Phone Number</p>
                    <p class="text-base font-mono">{{ viewingParcel.sender.phone }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">National ID</p>
                    <p class="text-base font-mono">{{ viewingParcel.sender.national_id_no }}</p>
                  </div>
                  <div class="pt-2 border-t">
                    <p class="text-xs text-gray-500 uppercase tracking-wide flex items-center gap-1">
                      <MapPin class="h-3 w-3" />
                      Origin
                    </p>
                    <p class="text-base font-semibold text-blue-600">{{ viewingParcel.origin_town }}</p>
                  </div>
                </CardContent>
              </Card>

              <!-- Recipient Information -->
              <Card class="border-2">
                <CardHeader class="pb-3 bg-green-50">
                  <CardTitle class="text-lg flex items-center gap-2">
                    <User class="h-5 w-5 text-green-600" />
                    Recipient Information
                  </CardTitle>
                </CardHeader>
                <CardContent class="pt-4 space-y-3">
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Full Name</p>
                    <p class="text-base font-semibold">{{ viewingParcel.recipient.full_name }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Phone Number</p>
                    <p class="text-base font-mono">{{ viewingParcel.recipient.phone }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">National ID</p>
                    <p class="text-base font-mono">{{ viewingParcel.recipient.national_id_no }}</p>
                  </div>
                  <div class="pt-2 border-t">
                    <p class="text-xs text-gray-500 uppercase tracking-wide flex items-center gap-1">
                      <MapPin class="h-3 w-3" />
                      Destination
                    </p>
                    <p class="text-base font-semibold text-green-600">{{ viewingParcel.destination_town }}</p>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Delivery Address -->
            <Card class="border-2 border-purple-200">
              <CardHeader class="pb-3 bg-purple-50">
                <CardTitle class="text-lg flex items-center gap-2">
                  <MapPin class="h-5 w-5 text-purple-600" />
                  Delivery Address
                </CardTitle>
              </CardHeader>
              <CardContent class="pt-4">
                <p class="text-base leading-relaxed">{{ viewingParcel.destination_address }}</p>
              </CardContent>
            </Card>

            <!-- Payment Information -->
            <Card class="border-2 border-orange-200">
              <CardHeader class="pb-3 bg-orange-50">
                <CardTitle class="text-lg flex items-center gap-2">
                  <Banknote class="h-5 w-5 text-orange-600" />
                  Payment Information
                </CardTitle>
              </CardHeader>
              <CardContent class="pt-4">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Amount</p>
                    <p class="text-2xl font-bold text-orange-600">KES {{ parseFloat(viewingParcel.amount).toLocaleString() }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">M-Pesa Number</p>
                    <p class="text-base font-mono">{{ viewingParcel.payment_phone }}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Timestamps -->
            <div class="grid grid-cols-2 gap-4 pt-4 border-t">
              <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Created</p>
                <p class="text-sm">{{ formatDate(viewingParcel.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Last Updated</p>
                <p class="text-sm">{{ formatDate(viewingParcel.updated_at) }}</p>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <Button type="button" variant="outline" @click="viewDialogOpen = false">
                Close
              </Button>
              <Button type="button" @click="() => { viewDialogOpen = false; editParcel(viewingParcel); }">
                Edit Parcel
              </Button>
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
<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const props = defineProps({
  customers: Array
})

const breadcrumbs = [
  {
    title: 'Dashboard',
    href: dashboard().url,
  },
  {
    title: 'Customers',
  }
]

const form = useForm({
  name: '',
  phone: '',
  email: '',
  address: ''
})

function submit() {
  form.post('/customers', {
    onSuccess: () => form.reset()
  })
}
</script>

<template>
  <Head title="Customers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">

      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>Add New Customer</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <Input v-model="form.name" placeholder="Name" required />
            <Input v-model="form.phone" placeholder="Phone" required />
            <Input v-model="form.email" type="email" placeholder="Email" />
            <Input v-model="form.address" placeholder="Address" />
            <Button type="submit">Add Customer</Button>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>All Customers</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Email</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="customer in customers" :key="customer.id">
                <TableCell>{{ customer.name }}</TableCell>
                <TableCell>{{ customer.phone }}</TableCell>
                <TableCell>{{ customer.email }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
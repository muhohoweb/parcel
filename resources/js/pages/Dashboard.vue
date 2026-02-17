<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Package, DollarSign, Users, TrendingUp, Clock, Truck, CheckCircle, XCircle } from 'lucide-vue-next';
import { dashboard } from '@/routes';

interface Stats {
  totalParcels: number;
  pendingPayment: number;
  inTransit: number;
  delivered: number;
  totalRevenue: number;
  pendingRevenue: number;
  totalCustomers: number;
}

interface Parcel {
  id: number;
  tracking_number: string;
  sender: { full_name: string };
  recipient: { full_name: string };
  origin_town: string;
  destination_town: string;
  amount: number;
  status: string;
  created_at: string;
}

const props = defineProps<{
  stats: Stats;
  recentParcels: Parcel[];
  parcelsByStatus: Record<string, number>;
  revenueByMonth: Array<{ month: string; revenue: number }>;
}>();

const breadcrumbs = [
  {
    title: 'Dashboard',
    href: dashboard().url,
  },
];

function getStatusColor(status: string) {
  const colors = {
    'pending_payment': 'text-yellow-600',
    'received': 'text-blue-600',
    'in_transit': 'text-purple-600',
    'delivered': 'text-green-600'
  };
  return colors[status] || 'text-gray-600';
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('en-KE', {
    style: 'currency',
    currency: 'KES',
    minimumFractionDigits: 0
  }).format(amount);
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
}
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">

      <!-- Stats Grid -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Total Parcels -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Parcels</CardTitle>
            <Package class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.totalParcels }}</div>
            <p class="text-xs text-muted-foreground">
              All time
            </p>
          </CardContent>
        </Card>

        <!-- Total Revenue -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
            <DollarSign class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(stats.totalRevenue) }}</div>
            <p class="text-xs text-muted-foreground">
              {{ formatCurrency(stats.pendingRevenue) }} pending
            </p>
          </CardContent>
        </Card>

        <!-- In Transit -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">In Transit</CardTitle>
            <Truck class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.inTransit }}</div>
            <p class="text-xs text-muted-foreground">
              Active deliveries
            </p>
          </CardContent>
        </Card>

        <!-- Delivered -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Delivered</CardTitle>
            <CheckCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.delivered }}</div>
            <p class="text-xs text-muted-foreground">
              Completed deliveries
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Status Overview & Recent Parcels -->
      <div class="grid gap-4 md:grid-cols-7">

        <!-- Status Overview -->
        <Card class="md:col-span-3">
          <CardHeader>
            <CardTitle>Parcel Status Overview</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Clock class="h-4 w-4 text-yellow-600" />
                <span class="text-sm font-medium">Pending Payment</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-2xl font-bold">{{ stats.pendingPayment }}</span>
                <span class="text-xs text-muted-foreground">parcels</span>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Package class="h-4 w-4 text-blue-600" />
                <span class="text-sm font-medium">Received</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-2xl font-bold">{{ parcelsByStatus['received'] || 0 }}</span>
                <span class="text-xs text-muted-foreground">parcels</span>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Truck class="h-4 w-4 text-purple-600" />
                <span class="text-sm font-medium">In Transit</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-2xl font-bold">{{ stats.inTransit }}</span>
                <span class="text-xs text-muted-foreground">parcels</span>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <CheckCircle class="h-4 w-4 text-green-600" />
                <span class="text-sm font-medium">Delivered</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-2xl font-bold">{{ stats.delivered }}</span>
                <span class="text-xs text-muted-foreground">parcels</span>
              </div>
            </div>

            <div class="pt-4 border-t">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Users class="h-4 w-4 text-orange-600" />
                  <span class="text-sm font-medium">Total Customers</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-2xl font-bold">{{ stats.totalCustomers }}</span>
                  <span class="text-xs text-muted-foreground">users</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Parcels -->
        <Card class="md:col-span-4">
          <CardHeader>
            <CardTitle>Recent Parcels</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Tracking</TableHead>
                  <TableHead>Route</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="parcel in recentParcels" :key="parcel.id">
                  <TableCell class="font-mono text-xs">{{ parcel.tracking_number }}</TableCell>
                  <TableCell class="text-sm">
                    {{ parcel.origin_town }} → {{ parcel.destination_town }}
                  </TableCell>
                  <TableCell class="text-sm font-medium">{{ formatCurrency(parcel.amount) }}</TableCell>
                  <TableCell>
                                        <span :class="['text-xs font-medium', getStatusColor(parcel.status)]">
                                            {{ parcel.status.replace('_', ' ') }}
                                        </span>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      <!-- Revenue Chart (Simple Bar Representation) -->
      <Card>
        <CardHeader>
          <CardTitle>Revenue Trend (Last 6 Months)</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="month in revenueByMonth" :key="month.month" class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="font-medium">{{ month.month }}</span>
                <span class="font-bold text-orange-600">{{ formatCurrency(month.revenue) }}</span>
              </div>
              <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div
                    class="h-full bg-gradient-to-r from-orange-400 to-orange-600 rounded-full transition-all"
                    :style="{ width: `${(month.revenue / Math.max(...revenueByMonth.map(m => m.revenue))) * 100}%` }"
                ></div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
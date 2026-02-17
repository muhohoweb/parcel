<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import {BookOpen, BoxesIcon, DollarSignIcon, Folder, LayoutGrid, PieChartIcon, UsersIcon} from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import {type NavItem} from '@/types';
import AppLogo from './AppLogo.vue';
import {dashboard} from '@/routes';
import parcels from "@/routes/parcels";

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
  },
  {
    title: 'Parcels',
    href: parcels.index(),
    icon: BoxesIcon,
  },
  {
    title: 'Payments',
    href: parcels.index(),
    icon: DollarSignIcon,
  },
  {
    title: 'Customers',
    href: parcels.index(),
    icon: UsersIcon,
  },
  {
    title: 'Reports',
    href: parcels.index(),
    icon: PieChartIcon,
  },
];

const footerNavItems: NavItem[] = [

];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="dashboard()">
              <AppLogo/>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems"/>
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems"/>
      <NavUser/>
    </SidebarFooter>
  </Sidebar>
  <slot/>
</template>

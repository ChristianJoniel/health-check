<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Deferred, Head } from '@inertiajs/vue3';

interface HealthCheckFailure {
    id: number;
    project_name: string;
    error_message: string | null;
    response_code: number | null;
    response_time_ms: number | null;
    checked_at: string;
    checked_at_human: string;
}

interface PaginatedFailures {
    data: HealthCheckFailure[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    activeProjectsCount: number;
    failuresToday: number;
    uptimePercentage: number;
    failures?: PaginatedFailures;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function getUptimeColor(uptime: number): string {
    if (uptime >= 99) return 'text-green-600 dark:text-green-400';
    if (uptime >= 95) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
}

function getStatusCodeColor(code: number | null): string {
    if (!code || code === 0) return 'text-muted-foreground';
    if (code >= 500) return 'text-red-600 dark:text-red-400';
    if (code >= 400) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-muted-foreground';
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Stats Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Total Projects Card -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Projects Monitored</CardDescription>
                        <CardTitle class="text-4xl">
                            {{ activeProjectsCount }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Active endpoints being monitored
                        </p>
                    </CardContent>
                </Card>

                <!-- Failures Today Card -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Failures Today</CardDescription>
                        <CardTitle
                            class="text-4xl"
                            :class="{
                                'text-red-600 dark:text-red-400':
                                    failuresToday > 0,
                            }"
                        >
                            {{ failuresToday }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Failed health checks in last 24 hours
                        </p>
                    </CardContent>
                </Card>

                <!-- Uptime Card -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Uptime (24h)</CardDescription>
                        <CardTitle
                            class="text-4xl"
                            :class="getUptimeColor(uptimePercentage)"
                        >
                            {{ uptimePercentage }}%
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Overall health check success rate
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Failures History -->
            <Card class="flex-1">
                <CardHeader>
                    <CardTitle>Failure History</CardTitle>
                    <CardDescription>
                        Recent health check failures across all monitored
                        projects
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Deferred data="failures">
                        <template #fallback>
                            <div class="space-y-3">
                                <Skeleton class="h-10 w-full" />
                                <Skeleton class="h-10 w-full" />
                                <Skeleton class="h-10 w-full" />
                                <Skeleton class="h-10 w-full" />
                                <Skeleton class="h-10 w-full" />
                            </div>
                        </template>

                        <div
                            v-if="failures && failures.data.length === 0"
                            class="flex flex-col items-center justify-center py-12 text-center"
                        >
                            <div
                                class="mb-4 rounded-full bg-green-100 p-3 dark:bg-green-900/20"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-8 w-8 text-green-600 dark:text-green-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium">
                                All Systems Operational
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                No health check failures recorded.
                            </p>
                        </div>

                        <div v-else-if="failures" class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-border text-left"
                                    >
                                        <th class="pb-3 font-medium">
                                            Project
                                        </th>
                                        <th class="pb-3 font-medium">Error</th>
                                        <th class="pb-3 font-medium">Status</th>
                                        <th class="pb-3 font-medium">
                                            Response Time
                                        </th>
                                        <th class="pb-3 font-medium">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="failure in failures.data"
                                        :key="failure.id"
                                        class="border-b border-border/50 last:border-0"
                                    >
                                        <td class="py-3 font-medium">
                                            {{ failure.project_name }}
                                        </td>
                                        <td
                                            class="max-w-xs truncate py-3 text-muted-foreground"
                                            :title="failure.error_message ?? ''"
                                        >
                                            {{
                                                failure.error_message ||
                                                'Unknown error'
                                            }}
                                        </td>
                                        <td class="py-3">
                                            <span
                                                :class="
                                                    getStatusCodeColor(
                                                        failure.response_code,
                                                    )
                                                "
                                            >
                                                {{
                                                    failure.response_code ||
                                                    'N/A'
                                                }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-muted-foreground">
                                            {{
                                                failure.response_time_ms
                                                    ? `${failure.response_time_ms}ms`
                                                    : 'N/A'
                                            }}
                                        </td>
                                        <td
                                            class="py-3 text-muted-foreground"
                                            :title="failure.checked_at"
                                        >
                                            {{ failure.checked_at_human }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div
                                v-if="failures.last_page > 1"
                                class="mt-4 flex items-center justify-between border-t border-border pt-4"
                            >
                                <p class="text-sm text-muted-foreground">
                                    Showing page {{ failures.current_page }} of
                                    {{ failures.last_page }}
                                    ({{ failures.total }} total)
                                </p>
                                <div class="flex gap-1">
                                    <template
                                        v-for="link in failures.links"
                                        :key="link.label"
                                    >
                                        <a
                                            v-if="link.url"
                                            :href="link.url"
                                            class="rounded border border-border px-3 py-1 text-sm hover:bg-accent"
                                            :class="{
                                                'bg-primary text-primary-foreground':
                                                    link.active,
                                            }"
                                            v-html="link.label"
                                        />
                                        <span
                                            v-else
                                            class="rounded border border-border px-3 py-1 text-sm opacity-50"
                                            v-html="link.label"
                                        />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </Deferred>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/ProjectController';
import {
    destroy,
    store as storeEmail,
} from '@/actions/App/Http/Controllers/ProjectNotificationEmailController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Form, router } from '@inertiajs/vue3';
import { Mail, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface NotificationEmail {
    id: number;
    email: string;
    created_at: string;
}

interface ProjectResource {
    id: number;
    name: string;
    health_check_url: string;
    is_active: boolean;
    notification_emails?: NotificationEmail[];
}

const props = defineProps<{
    project: ProjectResource;
}>();

const isOpen = defineModel<boolean>('isOpen');
const emit = defineEmits<{
    success: [];
}>();

const emails = ref<NotificationEmail[]>([]);
const loading = ref(false);
const deleting = ref<number | null>(null);

const fetchEmails = async () => {
    loading.value = true;
    try {
        const response = await fetch(show.url(props.project.id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        emails.value = data.props.project.notification_emails?.data || [];
    } catch (error) {
        console.error('Failed to fetch emails:', error);
    } finally {
        loading.value = false;
    }
};

const handleDelete = (email: NotificationEmail) => {
    if (!confirm(`Remove ${email.email} from notifications?`)) {
        return;
    }

    deleting.value = email.id;
    router.delete(destroy.url(email.id), {
        preserveScroll: true,
        onSuccess: () => {
            fetchEmails();
            emit('success');
        },
        onFinish: () => {
            deleting.value = null;
        },
    });
};

const handleAddSuccess = () => {
    fetchEmails();
    emit('success');
};

watch(
    () => isOpen.value,
    (open) => {
        if (open) {
            fetchEmails();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Manage Notification Emails</DialogTitle>
                <DialogDescription>
                    Add or remove email addresses that receive alerts when "{{
                        project.name
                    }}" health checks fail.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6">
                <!-- Add Email Form -->
                <Form
                    v-bind="storeEmail.form(project.id)"
                    class="space-y-3"
                    v-slot="{ errors, processing, recentlySuccessful }"
                    @success="handleAddSuccess"
                >
                    <div class="grid gap-2">
                        <Label for="email">Add Email Address</Label>
                        <div class="flex gap-2">
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                required
                                placeholder="admin@example.com"
                                class="flex-1"
                            />
                            <Button type="submit" :disabled="processing">
                                <Mail class="mr-2 size-4" />
                                Add
                            </Button>
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-if="recentlySuccessful"
                            class="text-sm text-muted-foreground"
                        >
                            Email added.
                        </p>
                    </Transition>
                </Form>

                <!-- Email List -->
                <div class="border-t pt-4">
                    <h3 class="mb-3 text-sm font-medium">
                        Current Email Addresses
                    </h3>

                    <div
                        v-if="loading"
                        class="flex items-center justify-center py-8"
                    >
                        <Spinner class="size-6" />
                    </div>

                    <div
                        v-else-if="emails.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        <p>No notification emails configured.</p>
                        <p class="mt-1">
                            Add an email address above to receive alerts.
                        </p>
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="email in emails"
                            :key="email.id"
                            class="flex items-center justify-between rounded-md border bg-muted/30 p-3"
                        >
                            <div class="flex items-center gap-2">
                                <Mail class="size-4 text-muted-foreground" />
                                <span class="text-sm">{{ email.email }}</span>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                :disabled="deleting === email.id"
                                @click="handleDelete(email)"
                            >
                                <Spinner
                                    v-if="deleting === email.id"
                                    class="size-4"
                                />
                                <Trash2 v-else class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t pt-4">
                    <Button variant="outline" @click="isOpen = false">
                        Close
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

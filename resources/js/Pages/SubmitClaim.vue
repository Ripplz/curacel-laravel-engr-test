<template>
    <GuestLayout>
        <Head title="Submit Claim" />

        <div class="card-header">Submit A Claim</div>

        <div class="card-body">
            <form @submit.prevent="submitClaim">
                <div class="mb-4">
                    <label
                        for="insurer_code"
                        class="block text-sm font-medium text-gray-700"
                        >Insurer Code</label
                    >
                    <input
                        v-model="form.insurer_code"
                        type="text"
                        id="insurer_code"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    />
                </div>

                <div class="mb-4">
                    <label
                        for="provider_name"
                        class="block text-sm font-medium text-gray-700"
                        >Provider Name</label
                    >
                    <input
                        v-model="form.provider_name"
                        type="text"
                        id="provider_name"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    />
                </div>

                <div class="mb-4">
                    <label
                        for="encounter_date"
                        class="block text-sm font-medium text-gray-700"
                        >Encounter Date</label
                    >
                    <input
                        v-model="form.encounter_date"
                        type="date"
                        id="encounter_date"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    />
                </div>

                <div class="mb-4">
                    <label
                        for="specialty"
                        class="block text-sm font-medium text-gray-700"
                        >Specialty</label
                    >
                    <select
                        v-model="form.specialty"
                        id="specialty"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    >
                        <option value="cardiology">Cardiology</option>
                        <option value="orthopedics">Orthopedics</option>
                        <option value="general">General</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label
                        for="priority"
                        class="block text-sm font-medium text-gray-700"
                        >Priority</label
                    >
                    <select
                        v-model="form.priority"
                        id="priority"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    >
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700"
                        >Claim Items</label
                    >
                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="border p-4 mb-2 rounded"
                    >
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label
                                    :for="'name_' + index"
                                    class="block text-sm font-medium text-gray-700"
                                    >Name</label
                                >
                                <input
                                    v-model="item.name"
                                    type="text"
                                    :id="'name_' + index"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                            <div>
                                <label
                                    :for="'unit_price_' + index"
                                    class="block text-sm font-medium text-gray-700"
                                    >Unit Price</label
                                >
                                <input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    :id="'unit_price_' + index"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    @input="calculateSubtotal(index)"
                                />
                            </div>
                            <div>
                                <label
                                    :for="'quantity_' + index"
                                    class="block text-sm font-medium text-gray-700"
                                    >Quantity</label
                                >
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    :id="'quantity_' + index"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    @input="calculateSubtotal(index)"
                                />
                            </div>
                        </div>
                        <div class="mt-2">
                            <span
                                >Subtotal: ${{ item.subtotal.toFixed(2) }}</span
                            >
                        </div>
                        <button
                            type="button"
                            @click="removeItem(index)"
                            class="mt-2 text-red-600"
                        >
                            Remove
                        </button>
                    </div>
                    <button
                        type="button"
                        @click="addItem"
                        class="mt-2 bg-blue-500 text-white px-4 py-2 rounded"
                    >
                        Add Item
                    </button>
                </div>

                <div class="mb-4">
                    <label
                        for="total_value"
                        class="block text-sm font-medium text-gray-700"
                        >Total Claim Amount</label
                    >
                    <input
                        v-model="totalValue"
                        type="text"
                        id="total_value"
                        readonly
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                    />
                </div>

                <button
                    type="submit"
                    class="bg-green-500 text-white px-4 py-2 rounded"
                    :disabled="loading"
                >
                    {{ loading ? "Submitting..." : "Submit Claim" }}
                </button>
            </form>
        </div>
    </GuestLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { ref, computed } from "vue";
import axios from "axios";

const form = ref({
    insurer_code: "",
    provider_name: "",
    encounter_date: "",
    specialty: "",
    priority: 1,
    items: [{ name: "", unit_price: 0, quantity: 1, subtotal: 0 }],
});

const loading = ref(false);

const totalValue = computed(() => {
    return form.value.items
        .reduce((total, item) => total + item.subtotal, 0)
        .toFixed(2);
});

const addItem = () => {
    form.value.items.push({
        name: "",
        unit_price: 0,
        quantity: 1,
        subtotal: 0,
    });
};

const removeItem = (index) => {
    form.value.items.splice(index, 1);
};

const calculateSubtotal = (index) => {
    const item = form.value.items[index];
    item.subtotal = item.unit_price * item.quantity;
};

const submitClaim = async () => {
    loading.value = true;
    try {
        const response = await axios.post("/api/claims", form.value);
        alert("Claim submitted successfully!");
        // Reset form
        form.value = {
            insurer_code: "",
            provider_name: "",
            encounter_date: "",
            specialty: "",
            priority: 1,
            items: [{ name: "", unit_price: 0, quantity: 1, subtotal: 0 }],
        };
    } catch (error) {
        alert(
            "Error submitting claim: " +
                (error.response?.data?.message || error.message)
        );
    } finally {
        loading.value = false;
    }
};
</script>

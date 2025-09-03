<template>
  <table class="fixed-column">
    <thead>
      <tr>
        <th class="bg-secondary" />
      </tr>
    </thead>
    <tbody>
      <tr v-for="courseType in courseTypes" :key="courseType.code">
        <td class="bg-secondary text-white text-weight-bold">
          {{ courseType.name }}
        </td>
      </tr>
      <tr class="total-row">
        <td class="bg-secondary text-white text-weight-bold">
          Total
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
  defineProps({
    courseTypes: {
      type: Array,
      required: true
    }
  })
</script>

<style scoped lang="scss">
.fixed-column {
  position: sticky;
  left: 0;
  z-index: 2;
  box-shadow: 2px 0 5px -2px rgba(0, 0, 0, 0.2);
  border-collapse: collapse;
  min-width: 70px;

  th, td {
    padding: 8px;
    text-align: center;
    height: 60px;
  }

  .total-row {
    border-top: 2px solid #999;
  }
}
</style>

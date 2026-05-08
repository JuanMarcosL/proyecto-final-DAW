<template>
  <div>
    <button @click="testLogin">Probar login</button>
    <pre>{{ respuesta }}</pre>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref } from 'vue'

const respuesta = ref('')

async function testLogin() {
  try {
    const res = await axios.post('http://localhost:8000/api/login', {
      email: 'admin@fieldservice.com',
      password: 'password123'
    })
    respuesta.value = JSON.stringify(res.data, null, 2)
  } catch (error) {
    respuesta.value = error.message
  }
}
</script>

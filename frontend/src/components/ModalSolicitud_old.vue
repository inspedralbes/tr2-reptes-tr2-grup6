<script setup>
import { ref } from 'vue';

const props = defineProps({
  isOpen: Boolean,
  taller: {
    type: Object,
    default: () => ({ id: null, nom: 'Taller' })
  }
});

const emit = defineEmits(['close', 'confirm']);

const form = ref({
  alumnes: 25,
  data: '',
  comentaris: ''
});

const handleSubmit = () => {
  console.log('Enviando solicitud:', { ...form.value, taller_id: props.taller.id });
  emit('confirm', { ...form.value, taller_id: props.taller.id });
  form.value.comentaris = '';
};
</script>

<template>
  <div v-if="isOpen" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
    
    <div style="background: white; border-radius: 12px; box-shadow: 0 20px 25px rgba(0,0,0,0.15); width: 100%; max-width: 500px; margin: 20px;">
      
      <div style="background: #0F172A; color: white; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-weight: bold; font-size: 18px; margin: 0;">Sol·licitar Taller</h3>
        <button @click="$emit('close')" style="background: none; border: none; color: #ccc; font-size: 28px; cursor: pointer;">×</button>
      </div>

      <div style="padding: 24px;">
        <h4 style="font-size: 20px; font-weight: bold; color: #1f2937; margin: 0 0 16px 0;">{{ taller?.nom || 'Taller' }}</h4>
        
        <form @submit.prevent="handleSubmit" style="display: flex; flex-direction: column; gap: 16px;">
          
          <div>
            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Nombre d'alumnes previstos</label>
            <input v-model.number="form.alumnes" type="number" min="5" max="40" required
              style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
          </div>

          <div>
            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Data preferent (Opcional)</label>
            <input v-model="form.data" type="date"
              style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
          </div>

          <div>
            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Observacions</label>
            <textarea v-model="form.comentaris" rows="3"
              style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; font-family: Arial;"
              placeholder="Necessitats especials..."></textarea>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid #eee;">
            <button type="button" @click="$emit('close')" style="padding: 8px 16px; background: #f3f4f6; color: #666; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">
              Cancel·lar
            </button>
            <button type="submit" style="padding: 8px 16px; background: #3B82F6; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold;">
              Confirmar
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>


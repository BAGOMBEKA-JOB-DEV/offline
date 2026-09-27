import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusBadge from '@/components/StatusBadge.vue'
import StatCard from '@/components/StatCard.vue'

describe('StatusBadge', () => {
  it('renders Active status with green classes', () => {
    const wrapper = mount(StatusBadge, { props: { status: 'Active' } })
    expect(wrapper.text()).toContain('Active')
    expect(wrapper.classes()).toContain('bg-green-100')
  })

  it('renders Expiring status with yellow classes', () => {
    const wrapper = mount(StatusBadge, { props: { status: 'Expiring' } })
    expect(wrapper.text()).toContain('Expiring')
    expect(wrapper.classes()).toContain('bg-yellow-100')
  })

  it('renders Missing status with red classes', () => {
    const wrapper = mount(StatusBadge, { props: { status: 'Missing' } })
    expect(wrapper.text()).toContain('Missing')
    expect(wrapper.classes()).toContain('bg-red-100')
  })
})

describe('StatCard', () => {
  it('renders label and value', () => {
    const wrapper = mount(StatCard, { props: { label: 'Total', value: 42 } })
    expect(wrapper.text()).toContain('Total')
    expect(wrapper.text()).toContain('42')
  })

  it('applies color classes', () => {
    const wrapper = mount(StatCard, { props: { label: 'X', value: 1, color: 'green' } })
    expect(wrapper.classes()).toContain('bg-green-50')
  })
})
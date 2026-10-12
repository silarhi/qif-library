import { defineSilarhiDocs } from '@silarhi/docs-kit'

export default defineSilarhiDocs({
    slug: 'qif-library',
    title: 'QIF Library',
    description: 'A PHP library to parse and write QIF (Quicken Interchange Format) files.',
    sidebar: [
        { label: 'Getting started', items: [{ autogenerate: { directory: 'getting-started' } }] },
        { label: 'Guides', items: [{ autogenerate: { directory: 'guides' } }] },
        { label: 'Reference', items: [{ autogenerate: { directory: 'reference' } }] },
        { slug: 'upgrading' },
    ],
})

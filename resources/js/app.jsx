/* SweetNest OIMS - front-end entry point (built by Vite).
 *
 * The pages are rendered by Laravel (Blade). React takes over the parts that behave
 * like small apps: the order form, the map address picker, the stock forms, the
 * payment form and the dashboard chart. Each one is mounted where a Blade view puts
 *   <x-react name="OrderForm" :props="[...]" />
 * and still submits as a normal form, so the server-side validation is unchanged. */
import './ui.js';
import { createRoot } from 'react-dom/client';
import { components } from './components';

function mount(scope) {
  scope.querySelectorAll('[data-react]').forEach((root) => {
    if (root.dataset.mounted) return;
    const Component = components[root.dataset.react];
    if (!Component) {
      console.error(`No React component named "${root.dataset.react}"`);
      return;
    }
    const props = root.dataset.props ? JSON.parse(root.dataset.props) : {};
    root.dataset.mounted = '1';
    createRoot(root).render(<Component {...props} root={root} />);
  });
}

mount(document);
// The live search swaps the page's <main>; any island in the new content is mounted too.
document.addEventListener('main:replaced', () => mount(document.querySelector('main')));

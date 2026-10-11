/* The body of the "Edit item" pop-up on the order page: the cake details of one
 * item. The page's Edit buttons fire a react:fill event with that row's values. */
import { useEffect, useState } from 'react';
import CakeSpecFields, { SPEC_KEYS, emptySpecs } from './CakeSpecFields';

function specsFrom(values) {
  const next = emptySpecs();
  SPEC_KEYS.forEach((key) => { next[key] = values && values[key] != null ? String(values[key]) : ''; });
  return next;
}

export default function ItemSpecsForm({ values = null, errors: initialErrors = {}, options, photoMaxKb, photoMaxLabel, root }) {
  const [specs, setSpecs] = useState(() => specsFrom(values));
  const [image, setImage] = useState({ has: Boolean(values?.has_image), url: values?.image_url || null });
  const [removeImage, setRemoveImage] = useState(() => values?.remove_image === '1' || values?.remove_image === true);
  const [errors, setErrors] = useState(initialErrors);
  const [fillCount, setFillCount] = useState(0);

  useEffect(() => {
    // The page's Edit buttons fill the pop-up for their row; errors from an earlier save no longer apply.
    const onFill = (e) => {
      const v = e.detail || {};
      setSpecs(specsFrom(v));
      setImage({ has: Boolean(v.has_image), url: v.image_url || null });
      setRemoveImage(false);
      setErrors({});
      setFillCount((n) => n + 1); // remounts the fields, which also empties the file box
    };
    root.addEventListener('react:fill', onFill);
    return () => root.removeEventListener('react:fill', onFill);
  }, [root]);

  return (
    <CakeSpecFields key={fillCount} idp="ei_" nameFor={(field) => field} values={specs} onChange={(changes) => setSpecs((s) => ({ ...s, ...changes }))}
      err={(field) => errors[field]} options={options} photoMaxKb={photoMaxKb} photoMaxLabel={photoMaxLabel}
      hasImage={image.has} imageUrl={image.url} removeImage={removeImage} onRemoveImage={setRemoveImage} />
  );
}

// Stands in for the Google Maps script (no internet in the sandbox): every property / call / `new` returns another stub.
(function () {
  const mk = () => new Proxy(function () {}, {
    get: (t, k) => (k === Symbol.toPrimitive ? () => 0 : k === 'then' ? undefined : mk()),
    apply: () => mk(), construct: () => mk(),
  });
  window.google = { maps: mk() };
  if (typeof window.initMap === 'function') setTimeout(window.initMap, 0);
})();

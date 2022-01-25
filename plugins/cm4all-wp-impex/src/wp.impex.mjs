/*
  this acts a initial dependency and is right now empty
*/

import "./wp.impex.scss";

const NAMESPACE = "cm4all-impex";

const filters = {
  SLICE_REST_UNMARSHAL: "slice_rest_unmarshal",
  SLICE_REST_UPLOAD: "slice_rest_upload",
  NAMESPACE,
};

const actions = {
  NAMESPACE,
};

export { filters, actions };

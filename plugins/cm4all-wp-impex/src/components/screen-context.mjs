import element from "@wordpress/element";

const ContextProvider = element.createContext();

export default function useScreenContext() {
  return element.useContext(ContextProvider);
}

ScreenContext = {
  normalizeFilename(fileName) {
    return (
      fileName
        .replace(/[^a-z0-9\-_]/gi, "_")
        .replace(/(-+)|(_+)/gi, ($) => $[0])
        .toLowerCase()
        // allow a maximum of 32 characters
        .slice(-32)
    );
  },
  currentDateString() {
    const date = new Date();
    return `${date.getFullYear()}-${("0" + (date.getMonth() + 1)).slice(-2)}-${(
      "0" + date.getDate()
    ).slice(-2)}-${date.getHours()}-${date.getMinutes()}-${date.getSeconds()}`;
  },
};

export function ScreenContextProvider({ children }) {
  return (
    <ContextProvider.Provider value={ScreenContext}>
      {children}
    </ContextProvider.Provider>
  );
}
